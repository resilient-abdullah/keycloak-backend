<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KeycloakAuthController extends Controller
{
    // Step 1: Redirect user to Keycloak login
    public function redirectToKeycloak()
    {
        $query = http_build_query([
            'client_id' => config('services.keycloak.client_id'),
            'response_type' => 'code',
            'scope' => 'openid',
            'redirect_uri' => config('services.keycloak.redirect_uri'),
        ]);

        return redirect(
            config('services.keycloak.base_url') .
            '/realms/' . config('services.keycloak.realm') .
            '/protocol/openid-connect/auth?' . $query
        );
    }

    // Step 2: Handle callback and exchange code for token
    public function handleCallback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return response()->json(['error' => 'Authorization code not found'], 400);
        }

        $tokenResponse = Http::asForm()->post(
            config('services.keycloak.base_url') .
            '/realms/' . config('services.keycloak.realm') .
            '/protocol/openid-connect/token',
            [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.keycloak.client_id'),
                'code' => $code,
                'redirect_uri' => config('services.keycloak.redirect_uri'),
            ]
        );

        if ($tokenResponse->failed()) {
            return response()->json([
                'error' => 'Token request failed',
                'details' => $tokenResponse->body(),
            ], 500);
        }

        return response()->json($tokenResponse->json());
    }

    // Step 3: Logout
    public function logout(Request $request)
    {
        $idToken = $request->query('id_token');

        if (!$idToken) {
            return response()->json(['error' => 'id_token required for logout'], 400);
        }

        $logoutUrl = config('services.keycloak.base_url') .
            '/realms/' . config('services.keycloak.realm') .
            '/protocol/openid-connect/logout?' . http_build_query([
                'id_token_hint' => $idToken,
                'post_logout_redirect_uri' => url('/'),
            ]);

        return redirect($logoutUrl);
    }
}
