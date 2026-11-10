<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponse
{
    /**
     * Respons sukses standar (default 200 OK).
     * Amplop seragam: { success, message, data, meta? }
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Permintaan berhasil diproses.',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Respons pembuatan resource baru: 201 Created + header Location.
     */
    protected function createdResponse(
        mixed $data,
        string $locationUrl,
        string $message = 'Resource baru berhasil dibuat.'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201)
            ->header('Location', $locationUrl);
    }

    /**
     * Respons hapus sukses: 204 No Content (body benar-benar kosong).
     */
    protected function noContentResponse(): Response
    {
        return response()->noContent();
    }

    /**
     * Respons galat standar IETF RFC 7807 (Problem Details for HTTP APIs).
     */
    protected function problemResponse(
        string $title,
        string $detail,
        int $status,
        string $errorSlug = 'general-error',
        array $errors = []
    ): JsonResponse {
        $problem = [
            'type'     => url("/docs/errors/{$errorSlug}"),
            'title'    => $title,
            'status'   => $status,
            'detail'   => $detail,
            'instance' => request()->getRequestUri(),
        ];

        if (!empty($errors)) {
            $problem['errors'] = $errors;
        }

        return response()
            ->json($problem, $status)
            ->header('Content-Type', 'application/problem+json');
    }
}