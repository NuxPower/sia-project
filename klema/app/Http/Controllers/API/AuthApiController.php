<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthApiController extends Controller
{
    /**
     * Register a new user account.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'role' => ['nullable', 'string', 'in:farmer,admin'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'farmer',
        ]);

        event(new Registered($user));

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify your email address to continue.',
            'requires_verification' => true,
            'user' => $user,
        ], 201);
    }

    /**
     * Authenticate using personal access tokens.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
            'create_session' => ['sometimes', 'boolean'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address to continue.',
                'requires_verification' => true,
            ], 423);
        }

        $tokenName = $validated['device_name'] ?? 'klema-api-token';
        $tokenName = Str::limit($tokenName, 255, '');
        $expiration = $this->determineExpiration($request->boolean('remember'));
        $abilities = $user->defaultTokenAbilities();

        if ($request->boolean('remember')) {
            $abilities[] = 'token:remember';
        }

        $plainTextToken = $user->issueToken($tokenName, $expiration, array_values(array_unique($abilities)));

        if (! empty($validated['create_session'])) {
            Auth::guard('web')->login($user, $request->boolean('remember'));
            $request->session()?->regenerate();
        }

        return response()->json([
            'success' => true,
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $expiration ? $expiration * 60 : null,
            'abilities' => $abilities,
            'user' => $user->load('farms'),
        ]);
    }

    /**
     * Logout the authenticated API user and revoke tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            if ($request->boolean('all_devices')) {
                $user->tokens()->delete();
            } elseif ($token = $user->currentAccessToken()) {
                $token->delete();
            }
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * Issue a new token for the authenticated session user.
     */
    public function issueToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        $expiration = $this->determineExpiration(false);
        $plainTextToken = $user->issueToken('klema-session-token', $expiration);

        return response()->json([
            'success' => true,
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $expiration ? $expiration * 60 : null,
            'abilities' => $user->defaultTokenAbilities(),
        ]);
    }

    /**
     * Rotate the current access token and return a fresh token.
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        if (! $currentToken) {
            abort(401, 'Authentication required.');
        }

        if (! $this->tokenCanRefresh($currentToken)) {
            abort(403, 'Token refresh is not permitted for this device.');
        }

        $abilities = $currentToken->abilities ?? $user->defaultTokenAbilities();
        $remember = in_array('token:remember', $abilities, true);
        $expiration = $this->determineExpiration($remember);
        $tokenName = $currentToken->name ?? 'klema-api-token';

        $currentToken->delete();

        $expiresAt = $expiration ? now()->addMinutes($expiration) : null;
        $newToken = $user->createToken($tokenName, $abilities, $expiresAt);

        return response()->json([
            'success' => true,
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $expiration ? $expiration * 60 : null,
            'abilities' => $abilities,
        ]);
    }

    /**
     * List active personal access tokens for the authenticated user.
     */
    public function listTokens(Request $request): JsonResponse
    {
        $this->ensureTokenManager($request);

        $tokens = $request->user()->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'last_used_at', 'created_at', 'expires_at', 'abilities'])
            ->map(function (PersonalAccessToken $token) use ($request) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'created_at' => $token->created_at,
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                    'current' => $request->user()->currentAccessToken()?->id === $token->id,
                ];
            });

        return response()->json([
            'success' => true,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Revoke a specific personal access token.
     */
    public function revokeToken(Request $request, string $tokenId): JsonResponse
    {
        $this->ensureTokenManager($request);

        $token = $request->user()->tokens()->where('id', $tokenId)->firstOrFail();
        $isCurrent = $request->user()->currentAccessToken()?->id === $token->id;

        $token->delete();

        if ($isCurrent) {
            return response()->json([
                'success' => true,
                'message' => 'Token revoked. Please re-authenticate.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully.',
        ]);
    }

    /**
     * Resend the email verification link for the authenticated user.
     */
    public function sendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email address already verified.',
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification link sent successfully.',
        ]);
    }

    private function determineExpiration(bool $remember): ?int
    {
        $expiration = $remember ? config('sanctum.remember_expiration') : config('sanctum.expiration');

        return $expiration !== null ? (int) $expiration : null;
    }

    private function tokenCanRefresh(PersonalAccessToken $token): bool
    {
        if ($token->can('*')) {
            return true;
        }

        return $token->can('token:refresh');
    }

    private function ensureTokenManager(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            abort(401, 'Authentication required.');
        }

        if ($token->can('*') || $token->can('tokens:manage')) {
            return;
        }

        abort(403, 'Token management is not permitted for this device.');
    }
}

