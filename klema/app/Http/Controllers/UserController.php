<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of users (Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $usersQuery = User::withCount(['farms', 'activities', 'exports'])
            ->orderBy('created_at', 'desc');

        // Filter by role if provided
        if ($request->has('role')) {
            $usersQuery->where('role', $request->role);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $usersQuery->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $users = $usersQuery->paginate($perPage);

        return response()->json([
            'success' => true,
            'users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                    'farms_count' => $user->farms_count,
                    'activities_count' => $user->activities_count,
                    'exports_count' => $user->exports_count,
                    'created_at' => $user->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Store a newly created user (Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

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
            'role' => ['required', 'string', 'in:farmer'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'email_verified_at' => now(), // Admin-created users are auto-verified
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at,
            ],
        ], 201);
    }

    /**
     * Display the specified user (Admin only).
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load(['farms', 'activities', 'exports']);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at,
                'farms_count' => $user->farms->count(),
                'activities_count' => $user->activities->count(),
                'exports_count' => $user->exports->count(),
                'farms' => $user->farms->map(function($farm) {
                    return [
                        'farm_id' => $farm->farm_id,
                        'farm_name' => $farm->farm_name,
                        'latitude' => $farm->latitude,
                        'longitude' => $farm->longitude,
                    ];
                }),
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Update the specified user (Admin only).
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'confirmed',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'role' => ['sometimes', 'required', 'string', 'in:farmer'],
        ]);

        // Ensure role is always farmer
        if (isset($validated['role'])) {
            $validated['role'] = 'farmer';
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    /**
     * Remove the specified user (Admin only).
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        // Prevent admin from deleting themselves
        $currentUser = Auth::user();
        if ($currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Reset user password (Admin only).
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->authorize('resetPassword', $user);

        $validated = $request->validate([
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

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. All user sessions have been terminated.',
        ]);
    }

    /**
     * Get user sessions (Admin only).
     */
    public function getSessions(Request $request, User $user): JsonResponse
    {
        $this->authorize('manageSessions', $user);

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function($session) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
                ];
            });

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
            'total_sessions' => $sessions->count(),
        ]);
    }

    /**
     * Revoke user sessions (Admin only).
     */
    public function revokeSessions(Request $request, User $user): JsonResponse
    {
        $this->authorize('manageSessions', $user);

        $sessionId = $request->input('session_id');

        if ($sessionId) {
            // Revoke specific session
            DB::table('sessions')
                ->where('id', $sessionId)
                ->where('user_id', $user->id)
                ->delete();
        } else {
            // Revoke all sessions except current
            $currentSessionId = $request->session()->getId();
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        // Also revoke API tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => $sessionId ? 'Session revoked successfully' : 'All user sessions and tokens revoked successfully',
        ]);
    }
}

