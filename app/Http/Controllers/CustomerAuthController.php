<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Notifications\ResetPasswordRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. Validate dữ liệu
        $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|string|email|max:255|unique:customers",
            "password" => "required|string|min:6|confirmed", // 'confirmed' yêu cầu client gửi thêm trường password_confirmation
        ]);

        // 2. Tạo Customer mới
        $customer = Customer::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password), // Nhớ Hash mật khẩu!
        ]);

        // 3. Cấp token luôn để user đăng nhập ngay lập tức
        $token = $customer->createToken("customer-token")->plainTextToken;

        $data = [
            "access_token" => $token,
            "token_type" => "Bearer",
            "customer" => $customer,
        ];

        return $this->successResponse($data, "Đăng ký tài khoản thành công!");
    }

    public function login(Request $request)
    {
        $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);

        $customer = Customer::where("email", $request->email)->first();

        if (
            !$customer ||
            !Hash::check($request->password, $customer->password)
        ) {
            // Thay thế đoạn json cũ bằng hàm này
            return $this->errorResponse(
                "Thông tin đăng nhập không chính xác.",
                401,
            );
        }

        $token = $customer->createToken("customer-token")->plainTextToken;

        $data = [
            "access_token" => $token,
            "token_type" => "Bearer",
            "customer" => $customer,
        ];

        return $this->successResponse($data, "Đăng nhập thành công!");
    }

    public function googleLogin(Request $request)
    {
        try {
            // 1. Nhận code từ Frontend gửi lên
            $code = $request->input("code");
            if (!$code) {
                return $this->errorResponse(
                    "Authorization code is missing",
                    400,
                );
            }

            // 2. Cấu hình Google Client
            $client = new GoogleClient();
            $client->setClientId(env("GOOGLE_CLIENT_ID"));
            $client->setClientSecret(env("GOOGLE_CLIENT_SECRET"));
            // QUAN TRỌNG: Phải set là 'postmessage' để khớp với luồng React SPA
            $client->setRedirectUri("postmessage");

            $guzzleClient = new GuzzleClient(["verify" => false]);
            $client->setHttpClient($guzzleClient);

            // 3. Đổi code lấy token
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token["error"])) {
                return $this->errorResponse(
                    "Google Login Failed: " . $token["error"],
                    401,
                );
            }

            // 4. Verify ID Token để lấy thông tin user
            $payload = $client->verifyIdToken($token["id_token"]);
            if (!$payload) {
                return $this->errorResponse("Invalid ID Token", 401);
            }

            // 5. Tìm hoặc tạo Customer
            $email = $payload["email"];
            $customer = Customer::where("email", $email)->first();

            if (!$customer) {
                // Nếu chưa có thì tạo mới
                $customer = Customer::create([
                    "name" => $payload["name"],
                    "email" => $email,
                    "email_verified_at" => $payload["email_verified"]
                        ? now()
                        : null, // (Tuỳ chọn)
                    // Tạo password ngẫu nhiên vì login bằng Google không cần pass
                    "password" => Hash::make(Str::random(16)),
                    // 'google_id' => $payload['sub'] // (Khuyên dùng) Nên thêm cột google_id vào bảng customers
                ]);
            }

            // 6. Cấp Token Sanctum (Giống như login thường)
            $accessToken = $customer->createToken("customer-token")
                ->plainTextToken;

            $data = [
                "access_token" => $accessToken,
                "token_type" => "Bearer",
                "customer" => $customer,
            ];

            return $this->successResponse(
                $data,
                "Đăng nhập Google thành công!",
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Lỗi xác thực Google: " . $e->getMessage(),
                500,
            );
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, "Đã đăng xuất thành công");
    }

    public function sendMailForgotPassword(Request $request)
    {
        $request->validate(["email" => "required|email"]);

        $customer = Customer::where("email", $request->email)->first();

        if (!$customer) {
            // Trả về success true để tránh hacker dò email, hoặc trả về lỗi 404 tùy bạn
            return $this->errorResponse(
                "Email không tồn tại trong hệ thống.",
                404,
            );
        }

        // 1. Tạo token ngẫu nhiên
        $token = Str::random(60);

        // 2. Lưu token vào bảng password_reset_tokens
        // Xóa token cũ của email này nếu có
        DB::table("password_reset_tokens")
            ->where("email", $request->email)
            ->delete();

        DB::table("password_reset_tokens")->insert([
            "email" => $request->email,
            "token" => $token, // Không cần hash token ở đây nếu muốn đơn giản, hoặc hash nếu muốn bảo mật cao hơn (mặc định Laravel hash token user)
            "created_at" => Carbon::now(),
        ]);

        // 3. Gửi mail
        // Đảm bảo Model Customer có use Notifiable;
        $customer->notify(new ResetPasswordRequest($token));

        return $this->successResponse(
            null,
            "Thư xác nhận đã được gửi đến email của bạn.",
        );
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            "token" => "required",
            "email" => "required|email",
            "password" => "required|min:6|confirmed",
        ]);

        // 1. Kiểm tra token trong database
        $resetRecord = DB::table("password_reset_tokens")
            ->where("email", $request->email)
            ->where("token", $request->token)
            ->first();

        if (!$resetRecord) {
            return $this->errorResponse(
                "Token không hợp lệ hoặc sai email.",
                400,
            );
        }

        // 2. Kiểm tra thời hạn token (ví dụ: hết hạn sau 60 phút)
        $tokenCreatedAt = Carbon::parse($resetRecord->created_at);
        if ($tokenCreatedAt->addMinutes(5)->isPast()) {
            DB::table("password_reset_tokens")
                ->where("email", $request->email)
                ->delete();
            return $this->errorResponse(
                "Token đã hết hạn. Vui lòng gửi lại yêu cầu.",
                400,
            );
        }

        // 3. Cập nhật mật khẩu cho Customer
        $customer = Customer::where("email", $request->email)->first();
        if (!$customer) {
            return $this->errorResponse("Không tìm thấy người dùng.", 404);
        }

        $customer->password = Hash::make($request->password);
        $customer->save();

        // 4. Xóa token sau khi dùng xong
        DB::table("password_reset_tokens")
            ->where("email", $request->email)
            ->delete();

        return $this->successResponse(
            null,
            "Đổi mật khẩu thành công. Bạn có thể đăng nhập ngay.",
        );
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:255",
            "phone" => "max:10",
        ]);

        $customer = Customer::find(auth()->id());
        if (!$customer) {
            return $this->errorResponse("Không tìm thấy người dùng.", 404);
        }

        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->save();

        return $this->successResponse(
            $customer,
            "Cập nhật thông tin thành công.",
        );
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            "avatar" => "required|image|mimes:jpeg,png,jpg,gif|max:2048",
        ]);

        $customer = Customer::find(auth()->id());
        if (!$customer) {
            return $this->errorResponse("Không tìm thấy người dùng.", 404);
        }

        $avatar = $request->file("avatar");
        $avatarName = time() . "." . $avatar->getClientOriginalExtension();
        $avatar->move(public_path("avatars"), $avatarName);

        $customer->avatar = $avatarName;
        $customer->save();

        return $this->successResponse($customer, "Cập nhật avatar thành công.");
    }
}
