<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Requests\LoginRequest;
use App\Models\User;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController
{
    use ApiResponse;

    private const ROLE_ABILITIES = [
        'admin' => ['*'],
        'manager' => ['clients:read', 'clients:write', 'followups:manage', 'dashboard:view'],
        'sales_rep' => ['clients:read', 'communications:create', 'followups:manage'],
    ];

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::whereEmail($credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error('Invalid credentials', ['email' => ['These credentials do not match our records.']], 401);
        }

        // Revoke all previous tokens
        $user->tokens()->delete();

        // Create new token with abilities based on user role
        $abilities = $this->resolveAbilities($user);
        $token = $user->createToken('mini-crm', $abilities);

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'abilities' => $abilities,
            'user' => $user->load('roles', 'permissions'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'user' => $request->user()->load('roles', 'permissions'),
        ]);
    }

    /**
     * @return string[]
     */
    private function resolveAbilities(User $user): array
    {
        $role = $user->roles->pluck('name')->first() ?? $user->role;

        return self::ROLE_ABILITIES[$role] ?? ['clients:read'];
    }
}
