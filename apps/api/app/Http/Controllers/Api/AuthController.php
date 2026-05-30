<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 422);
        }

        [$token, $plainTextToken] = \App\Models\ApiToken::issueFor(
            $user,
            $validated['device_name'] ?? 'personal-device',
        );

        return response()->json([
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at?->toIso8601String(),
            'user' => $user,
            'portfolios' => $user->portfolios()->get(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
            'portfolios' => $request->user()->portfolios()->get(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('apiToken')?->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
