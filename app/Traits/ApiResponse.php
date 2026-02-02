<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Trả về response thành công (Success)
     */
    protected function successResponse($data, $message = 'Thành công', $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Trả về response lỗi (Error)
     */
    protected function errorResponse($message = 'Đã có lỗi xảy ra', $code = 400, $errors = null)
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
