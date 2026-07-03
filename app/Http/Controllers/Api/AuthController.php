<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('username', 'password'))) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        $token = $user->createToken('auth-token')->plainTextToken;

        $user->tokens()->latest()->first()->forceFill([
            'expires_at' => now()->addHours(6),
        ])->save();

        return response()->json([
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ]);
    }

    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($request->validated('user_id'));
        $user->password = $request->validated('password');
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente',
        ]);
    }
}
