<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    public function login(AuthRequest $request): JsonResponse
    {
        $token = $this->guard()->attempt($request->validated());

        if (! $token) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        return $this->tokenResponse($token);
    }

    /**
     * Troca o token atual por um novo. Aceita tokens de acesso já expirados,
     * desde que dentro da janela de refresh (JWT_REFRESH_TTL); o token
     * antigo é invalidado.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = $this->guard()->refresh();
        } catch (JWTException) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 401);
        }

        return $this->tokenResponse($token);
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function tokenResponse(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
        ]);
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return Auth::guard('api');
    }
}
