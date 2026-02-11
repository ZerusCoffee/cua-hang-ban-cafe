<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $customer = auth()->user();
            $addresses = Address::where("customer_id", $customer->id)
                ->orderBy("is_default", "desc")
                ->orderBy("created_at", "desc")
                ->get();

            return $this->successResponse(
                $addresses,
                "Lấy danh sách địa chỉ thành công",
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi lấy danh sách địa chỉ: " . $e->getMessage(),
                500,
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    "full_name" => "required|string|max:255",
                    "phone" => 'required|string|max:15|regex:/^[0-9]+$/',
                    "details" => "required|string|max:500",
                    "ward" => "required|string|max:255",
                    "province" => "required|string|max:255",
                    "is_default" => "boolean",
                ],
                [
                    "full_name.required" => "Họ tên là bắt buộc",
                    "phone.required" => "Số điện thoại là bắt buộc",
                    "phone.regex" => "Số điện thoại chỉ được chứa số",
                    "details.required" => "Địa chỉ chi tiết là bắt buộc",
                    "ward.required" => "Phường/Xã là bắt buộc",
                    "province.required" => "Tỉnh/Thành phố là bắt buộc",
                ],
            );

            if ($validator->fails()) {
                return $this->errorResponse(
                    "Dữ liệu không hợp lệ",
                    422,
                    $validator->errors()->toArray(),
                );
            }

            $customer = auth()->user();

            $addressData = [
                "customer_id" => $customer->id,
                "full_name" => $request->full_name,
                "phone" => $request->phone,
                "details" => $request->details,
                "ward" => $request->ward,
                "province" => $request->province,
                "is_default" => $request->boolean("is_default", false),
            ];

            $address = Address::create($addressData);

            return $this->successResponse(
                $address,
                "Thêm địa chỉ thành công",
                201,
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi thêm địa chỉ: " . $e->getMessage(),
                500,
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Address $address)
    {
        try {
            $customer = auth()->user();

            if ($address->customer_id !== $customer->id) {
                return $this->errorResponse(
                    "Bạn không có quyền truy cập địa chỉ này",
                    403,
                );
            }

            return $this->successResponse(
                $address,
                "Lấy thông tin địa chỉ thành công",
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi lấy thông tin địa chỉ: " . $e->getMessage(),
                500,
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Address $address)
    {
        try {
            $customer = auth()->user();

            // Kiểm tra quyền truy cập
            if ($address->customer_id !== $customer->id) {
                return $this->errorResponse(
                    "Bạn không có quyền cập nhật địa chỉ này",
                    403,
                );
            }

            $validator = Validator::make(
                $request->all(),
                [
                    "full_name" => "required|string|max:255",
                    "phone" => 'required|string|max:15|regex:/^[0-9]+$/',
                    "details" => "required|string|max:500",
                    "ward" => "required|string|max:255",
                    "province" => "required|string|max:255",
                    "is_default" => "boolean",
                ],
                [
                    "full_name.required" => "Họ tên là bắt buộc",
                    "phone.required" => "Số điện thoại là bắt buộc",
                    "phone.regex" => "Số điện thoại chỉ được chứa số",
                    "details.required" => "Địa chỉ chi tiết là bắt buộc",
                    "ward.required" => "Phường/Xã là bắt buộc",
                    "province.required" => "Tỉnh/Thành phố là bắt buộc",
                ],
            );

            if ($validator->fails()) {
                return $this->errorResponse(
                    "Dữ liệu không hợp lệ",
                    422,
                    $validator->errors()->toArray(),
                );
            }

            $addressData = [
                "full_name" => $request->full_name,
                "phone" => $request->phone,
                "details" => $request->details,
                "ward" => $request->ward,
                "province" => $request->province,
            ];

            if ($request->has("is_default")) {
                if ($address->is_default && !$request->boolean("is_default")) {
                    return $this->errorResponse(
                        "Không thể hủy đặt địa chỉ mặc định. Vui lòng chọn một địa chỉ khác làm mặc định trước.",
                        422,
                    );
                }

                $addressData["is_default"] = $request->boolean("is_default");
            }

            $address->update($addressData);

            return $this->successResponse(
                $address,
                "Cập nhật địa chỉ thành công",
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi cập nhật địa chỉ: " . $e->getMessage(),
                500,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address)
    {
        try {
            $customer = auth()->user();

            // Kiểm tra quyền truy cập
            if ($address->customer_id !== $customer->id) {
                return $this->errorResponse(
                    "Bạn không có quyền xóa địa chỉ này",
                    403,
                );
            }

            // Kiểm tra nếu đây là địa chỉ cuối cùng
            $addressCount = Address::where(
                "customer_id",
                $customer->id,
            )->count();

            if ($address->is_default) {
                $newDefaultAddress = Address::where(
                    "customer_id",
                    $customer->id,
                )
                    ->where("id", "!=", $address->id)
                    ->first();

                if ($newDefaultAddress) {
                    $newDefaultAddress->update(["is_default" => true]);
                }
            }

            $address->delete();

            return $this->successResponse(null, "Xóa địa chỉ thành công");
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi xóa địa chỉ: " . $e->getMessage(),
                500,
            );
        }
    }

    public function setDefault(Address $address)
    {
        try {
            $customer = auth()->user();

            // Kiểm tra quyền truy cập
            if ($address->customer_id !== $customer->id) {
                return $this->errorResponse(
                    "Bạn không có quyền thao tác với địa chỉ này",
                    403,
                );
            }

            // Cập nhật tất cả địa chỉ của customer về không mặc định
            Address::where("customer_id", $customer->id)->update([
                "is_default" => false,
            ]);

            // Đặt địa chỉ này làm mặc định
            $address->update(["is_default" => true]);

            return $this->successResponse(
                $address,
                "Đặt địa chỉ mặc định thành công",
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi đặt địa chỉ mặc định: " . $e->getMessage(),
                500,
            );
        }
    }

    public function getDefault()
    {
        try {
            $customer = auth()->user();

            $defaultAddress = Address::where("customer_id", $customer->id)
                ->where("is_default", true)
                ->first();

            // Nếu không có địa chỉ mặc định, trả về địa chỉ đầu tiên
            if (!$defaultAddress) {
                $defaultAddress = Address::where("customer_id", $customer->id)
                    ->orderBy("created_at", "desc")
                    ->first();
            }

            if (!$defaultAddress) {
                return $this->errorResponse("Không tìm thấy địa chỉ", 404);
            }

            return $this->successResponse(
                $defaultAddress,
                "Lấy địa chỉ mặc định thành công",
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi lấy địa chỉ mặc định: " . $e->getMessage(),
                500,
            );
        }
    }

    public function getProvinces()
    {
        try {
            $provinces = Cache::remember("provinces", 86400, function () {
                $response = Http::withoutVerifying()
                    ->timeout(30)
                    ->get("https://provinces.open-api.vn/api/v2/p/");
                if ($response->failed()) {
                    throw new \Exception(
                        "Không thể lấy dữ liệu tỉnh/thành phố",
                    );
                }

                return $response->json();
            });

            return $this->successResponse(
                $provinces,
                "Lấy danh sách tỉnh/thành phố thành công",
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Lỗi khi lấy dữ liệu: " . $e->getMessage(),
                500,
            );
        }
    }

    public function getWardsByProvinceCode($provinceCode = null)
    {
        try {
            if (!$provinceCode) {
                return $this->errorResponse("Mã phường/xã không hợp lệ", 400);
            }
            $wards = Cache::remember(
                "wards_{$provinceCode}",
                86400,
                function () use ($provinceCode) {
                    $response = Http::withoutVerifying()
                        ->timeout(30)
                        ->get(
                            "https://provinces.open-api.vn/api/v2/p/" .
                                $provinceCode .
                                "?depth=2",
                        );
                    if ($response->failed()) {
                        throw new \Exception("Không thể lấy dữ liệu phường/xã");
                    }

                    $data = $response->json();
                    return $data["wards"] ?? [];
                },
            );

            return $this->successResponse(
                $wards,
                "Lấy danh sách phường/xã thành công",
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
