<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Usage: ->middleware('role:admin') or ->middleware('role:mechanic,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
                'errors' => [],
            ], 403);
        }

        return $next($request);
    }
}
