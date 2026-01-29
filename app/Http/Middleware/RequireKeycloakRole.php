<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireKeycloakRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->get('keycloak_user');

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $roles = $user->realm_access->roles ?? [];

        if (!in_array($role, $roles)) {
            return response()->json([
                'error' => 'Forbidden',
                'required_role' => $role,
            ], 403);
        }

        return $next($request);
    }
}
