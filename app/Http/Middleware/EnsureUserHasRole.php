<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = array_filter(
            $roles,
            fn (string $role): bool => UserRole::tryFrom(strtoupper($role)) !== null,
        );

        if ($user === null || ! in_array($user->role, array_map(fn (string $role) => UserRole::from(strtoupper($role)), $allowed), true)) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
                'data' => [],
            ], 403);
        }

        return $next($request);
    }
}
