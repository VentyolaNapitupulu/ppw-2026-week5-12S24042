<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthTokenController extends Controller
{
    use ApiResponse;

    /** Masa berlaku token (jam). Token kedaluwarsa otomatis ditolak 401. */
    private const TOKEN_TTL_HOURS = 8;

    /**
     * POST /api/v1/auth/login
     * Verifikasi kredensial pustakawan dan terbitkan Bearer Token (stateless).
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Pesan sengaja disamakan: tidak membocorkan apakah email terdaftar atau tidak
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->problemResponse(
                title: 'Invalid Credentials',
                detail: 'Kombinasi email atau password yang diberikan tidak cocok.',
                status: 401,
                errorSlug: 'invalid-credentials'
            );
        }

        $expiresAt = now()->addHours(self::TOKEN_TTL_HOURS);
        $token = $user->createToken(
            $validated['device_name'] ?? 'postman-client',
            ['*'],
            $expiresAt
        );

        return $this->successResponse(
            data: [
                'token_type'   => 'Bearer',
                'access_token' => $token->plainTextToken,
                'expires_at'   => $expiresAt->toIso8601String(),
                'user'         => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ],
            message: 'Otentikasi API berhasil. Lampirkan access_token pada header Authorization: Bearer <token>.'
        );
    }

    /**
     * GET /api/v1/auth/me  (auth:sanctum)
     * Profil pemilik token + info token yang sedang dipakai.
     */
    public function me(Request $request): JsonResponse
    {
        $user  = $request->user();
        $token = $user->currentAccessToken();

        return $this->successResponse(
            data: [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'token' => [
                    'name'         => $token->name,
                    'created_at'   => $token->created_at?->toIso8601String(),
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'expires_at'   => $token->expires_at?->toIso8601String(),
                ],
            ],
            message: 'Profil pemilik token aktif berhasil diambil.'
        );
    }

    /**
     * POST /api/v1/auth/logout  (auth:sanctum)
     * Cabut (revoke) HANYA token yang sedang dipakai; token di perangkat lain tetap aktif.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            data: null,
            message: 'Bearer Token berhasil dicabut (revoked). Sesi API diakhiri.'
        );
    }
}