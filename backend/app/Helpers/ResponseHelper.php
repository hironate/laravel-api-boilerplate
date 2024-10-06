<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

if (!function_exists('respond')) {
    function respond($message, $data, $statusCode = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'statusCode' => $statusCode,
        ], $statusCode);
    }
}
