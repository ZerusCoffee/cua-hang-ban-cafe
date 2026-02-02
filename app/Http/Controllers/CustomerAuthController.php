<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Notifications\ResetPasswordRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        //Validate dữ liệu
        $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|string|email|max:255|unique:customers",
            "password" => "required|string|min:6|confirmed", // 'confirmed' yêu cầu client gửi thêm trường password_confirmation
        ]);

        // Tạo Customer mới
        $customer = Customer::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password), //Hash mật khẩu!
            "is_locked" => false,
        ]);

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

        if ($customer && $customer->is_locked) {
            return $this->errorResponse(
                "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.",
                403,
            );
        }

        if (
            !$customer ||
            !Hash::check($request->password, $customer->password)
        ) {
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
            $code = $request->input("code");
            if (!$code) {
                return $this->errorResponse(
                    "Authorization code is missing",
                    400,
                );
            }

            $client = new GoogleClient();
            $client->setClientId(env("GOOGLE_CLIENT_ID"));
            $client->setClientSecret(env("GOOGLE_CLIENT_SECRET"));
            $client->setRedirectUri("postmessage");

            $guzzleClient = new GuzzleClient(["verify" => false]);
            $client->setHttpClient($guzzleClient);

            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token["error"])) {
                return $this->errorResponse(
                    "Google Login Failed: " . $token["error"],
                    401,
                );
            }

            $payload = $client->verifyIdToken($token["id_token"]);
            if (!$payload) {
                return $this->errorResponse("Invalid ID Token", 401);
            }

            $email = $payload["email"];
            $customer = Customer::where("email", $email)->first();

            if ($customer && $customer->is_locked) {
                return $this->errorResponse(
                    "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.",
                    403,
                );
            }

            if (!$customer) {
                // Nếu chưa có thì tạo mới
                $customer = Customer::create([
                    "name" => $payload["name"],
                    "email" => $email,
                    "email_verified_at" => $payload["email_verified"]
                        ? now()
                        : null,
                    "password" => Hash::make(Str::random(16)),
                    "is_locked" => false,
                ]);
            }

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

        if ($customer->is_locked) {
            return $this->errorResponse(
                "Tài khoản của bạn đã bị khóa. Không thể thực hiện yêu cầu này.",
                403,
            );
        }

        if (!$customer) {
            return $this->errorResponse(
                "Email không tồn tại trong hệ thống.",
                404,
            );
        }

        $token = Str::random(60);

        DB::table("password_reset_tokens")
            ->where("email", $request->email)
            ->delete();

        DB::table("password_reset_tokens")->insert([
            "email" => $request->email,
            "token" => $token, // Thích thì Hash Token ở đây
            "created_at" => Carbon::now(),
        ]);

        $customer->notify(new ResetPasswordRequest($token)); // gửi vô mail

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

        // Kiểm tra token đã hết hạn sau mỗi 5p
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

        $customer = Customer::where("email", $request->email)->first();
        if (!$customer) {
            return $this->errorResponse("Không tìm thấy người dùng.", 404);
        }

        if ($customer->is_locked) {
            return $this->errorResponse(
                "Tài khoản của bạn đã bị khóa. Không thể thực hiện yêu cầu này.",
                403,
            );
        }

        $customer->password = Hash::make($request->password);
        $customer->save();

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

        if ($customer->is_locked) {
            return $this->errorResponse(
                "Tài khoản của bạn đã bị khóa. Không thể thực hiện yêu cầu này.",
                403,
            );
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

        if ($customer->is_locked) {
            return $this->errorResponse(
                "Tài khoản của bạn đã bị khóa. Không thể thực hiện yêu cầu này.",
                403,
            );
        }

        if (
            $customer->avatar &&
            Storage::disk("public")->exists($customer->avatar)
        ) {
            Storage::disk("public")->delete($customer->avatar);
        }

        $path = $request->file("avatar")->store("avatars", "public");

        $customer->avatar = $path;
        $customer->save();

        return $this->successResponse(
            [
                ...$customer->toArray(),
                "avatar_url" => asset("storage/" . $path),
            ],
            "Cập nhật avatar thành công.",
        );
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            "current_password" => "required|string|min:6",
            "new_password" => "required|string|min:6",
            "new_password_confirmation" => "required|string|min:6",
        ]);

        if ($request->new_password !== $request->new_password_confirmation) {
            return $this->errorResponse(
                "Mật khẩu xác nhận không khớp.",
                422
            );
        }

        $customer = Customer::find(auth()->id());
        if (!$customer) {
            return $this->errorResponse("Không tìm thấy người dùng.", 404);
        }

         if ($customer->is_locked) {
            return $this->errorResponse(
                "Tài khoản của bạn đã bị khóa. Không thể thực hiện yêu cầu này.",
                403,
            );
        }

        if (!Hash::check($request->current_password, $customer->password)) {
            return $this->errorResponse(
                "Mật khẩu hiện tại không chính xác.",
                422
            );
        }

        if (Hash::check($request->new_password, $customer->password)) {
            return $this->errorResponse(
                "Mật khẩu mới không được trùng với mật khẩu hiện tại.",
                422
            );
        }

        $customer->password = Hash::make($request->new_password);
        $customer->save();

        $currentTokenId = $request->user()->currentAccessToken()->id;
        $customer->tokens()->where('id', '!=', $currentTokenId)->delete(); //logout all device

        return $this->successResponse(
            null,
            "Đổi mật khẩu thành công!"
        );
    }
}
