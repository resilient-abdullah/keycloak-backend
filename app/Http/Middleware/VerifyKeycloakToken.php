<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use phpseclib3\Crypt\RSA;
use phpseclib3\Math\BigInteger;

class VerifyKeycloakToken
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $authHeader = $request->header('Authorization');

            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response()->json(['error' => 'Missing token'], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);

            // 1️⃣ Fetch Keycloak JWKS (cached for 1 hour)
            $jwksUrl = config('services.keycloak.base_url')
                . '/realms/' . config('services.keycloak.realm')
                . '/protocol/openid-connect/certs';

            $jwks = Cache::remember('keycloak_jwks', 3600, function () use ($jwksUrl) {
                $response = Http::get($jwksUrl);
                if (!$response->ok()) {
                    throw new \Exception('Unable to fetch JWKS from Keycloak');
                }
                return $response->json();
            });

            if (!isset($jwks['keys'])) {
                throw new \Exception('JWKS keys not found');
            }

            // 2️⃣ Decode token header to get KID
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                return response()->json(['error' => 'Invalid token format'], 401);
            }

            $header = json_decode(base64_decode($tokenParts[0]), true);
            $kid = $header['kid'] ?? null;

            if (!$kid) {
                return response()->json(['error' => 'Token missing kid'], 401);
            }

            // 3️⃣ Find matching public key
            $publicKey = null;
            foreach ($jwks['keys'] as $jwk) {
                if ($jwk['kid'] === $kid) {
                    $publicKey = $this->jwkToPem($jwk);
                    break;
                }
            }

            if (!$publicKey) {
                return response()->json(['error' => 'Invalid token key'], 401);
            }

            // 4️⃣ Decode & verify token
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            // 5️⃣ Validate issuer
            $expectedIssuer = config('services.keycloak.base_url')
                . '/realms/' . config('services.keycloak.realm');

            if ($decoded->iss !== $expectedIssuer) {
                return response()->json(['error' => 'Invalid token issuer'], 401);
            }

            // 6️⃣ Attach user info to request
            $request->attributes->add(['keycloak_user' => $decoded]);

            return $next($request);

        } catch (ExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Convert JWK to PEM public key using phpseclib3
     */
    private function jwkToPem(array $jwk): string
    {
        $n = new BigInteger($this->base64UrlDecode($jwk['n']), 256);
        $e = new BigInteger($this->base64UrlDecode($jwk['e']), 256);

        $rsa = RSA::loadPublicKey([
            'n' => $n,
            'e' => $e,
        ]);

        // Export PEM compatible with RS256
        return $rsa->toString('PKCS8');
    }

    /**
     * Base64Url decode helper
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
