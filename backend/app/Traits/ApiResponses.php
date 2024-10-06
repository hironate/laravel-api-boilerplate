<?php

namespace App\Traits;

trait ApiResponses
{

    protected function ok($message)
    {
        return $this->success($message, null);
    }

    protected function badRequest($message, $data = null)
    {
        return $this->respond($message, $data, 400);
    }

    protected function success($message, $data = null, $statusCode = 200)
    {
        return $this->respond($message, $data, $statusCode);
    }

    protected function unauthorized($message, $data = null)
    {
        return $this->respond($message, $data, 401);
    }

    protected function forbidden($message, $data = null)
    {
        return $this->respond($message, $data, 403);
    }

    protected function notFound($message, $data = null)
    {
        return $this->respond($message, $data, 404);
    }

    protected function serverError($message, $data = null)
    {
        return $this->respond($message, $data, 500);
    }

    protected function tooManyRequests($message, $data = null)
    {
        return $this->respond($message, $data, 429);
    }

    protected function error($message, $data = null, $statusCode = 400)
    {
        return $this->respond($message, $data, $statusCode);
    }

    protected function respond($message, $data, $statusCode = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'statusCode' => $statusCode,
        ], $statusCode);
    }
}
