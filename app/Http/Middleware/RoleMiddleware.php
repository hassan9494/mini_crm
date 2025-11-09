<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
        }

        $allowedRoles = collect($roles)
            ->flatMap(fn (string $roleSet) => explode('|', $roleSet))
            ->filter()
            ->unique();

        if ($allowedRoles->isEmpty()) {
            return $next($request);
        }

        if (! $user->hasAnyRole($allowedRoles->all())) {
            abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        return $next($request);
    }
}
