<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication required.');
        }

        $normalizedRoles = array_map(fn ($role) => strtolower(trim($role)), $roles);

        if ($this->userHasRole($user->role, $normalizedRoles)) {
            return $next($request);
        }

        $token = $user->currentAccessToken();

        if ($token) {
            if ($token->can('*')) {
                return $next($request);
            }

            foreach ($normalizedRoles as $role) {
                if ($token->can("role:{$role}")) {
                    return $next($request);
                }
            }
        }

        abort(Response::HTTP_FORBIDDEN, 'You do not have permission to perform this action.');
    }

    /**
     * Determine if the user's role matches the allowed roles.
     *
     * @param  string|null  $userRole
     * @param  array<int, string>  $allowedRoles
     */
    private function userHasRole(?string $userRole, array $allowedRoles): bool
    {
        if ($userRole === null) {
            return false;
        }

        return in_array(strtolower($userRole), $allowedRoles, true);
    }
}

