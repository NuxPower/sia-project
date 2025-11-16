<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;

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
            'role' => ['nullable', 'string', 'in:farmer'],
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
        $expiration = config('sanctum.expiration');

        $plainTextToken = $user->issueToken($tokenName, $expiration);

        if (! empty($validated['create_session'])) {
            Auth::guard('web')->login($user, $request->boolean('remember'));
            $request->session()?->regenerate();
        }

            return response()->json([
                'success' => true,
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $expiration ? $expiration * 60 : null,
            'abilities' => $user->defaultTokenAbilities(),
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

        $expiration = config('sanctum.expiration');
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

    public function resendVerificationForEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => true,
                'message' => 'If the account exists, we have sent another verification email.',
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Your email is already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent successfully.',
        ]);
    }

    public function sendPasswordResetLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = PasswordBroker::broker()->sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset instructions sent if the account exists.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
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
        ]);

        $status = PasswordBroker::broker()->reset(
            $validated,
            function ($user) use ($validated) {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now sign in.',
        ]);
    }
}

