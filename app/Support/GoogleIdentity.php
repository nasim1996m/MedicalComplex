<?php

namespace App\Support;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;

/**
 * Verifies Google sign-in tokens and returns the verified identity, or null.
 * Both paths check that the token was issued to OUR OAuth client (audience),
 * otherwise a token obtained by any other Google app could be replayed here.
 */
class GoogleIdentity
{
    /** @return array{email:string,name:string,google_id:string,avatar:?string}|null */
    public static function fromIdToken(string $idToken): ?array
    {
        $clientId = self::clientId();
        if (!$clientId) {
            return null;
        }
        try {
            $payload = (new GoogleClient(['client_id' => $clientId]))->verifyIdToken($idToken);
        } catch (\Throwable) {
            return null;
        }
        if (!$payload || empty($payload['email']) || empty($payload['email_verified'])) {
            return null;
        }

        return self::identity($payload);
    }

    /** @return array{email:string,name:string,google_id:string,avatar:?string}|null */
    public static function fromAccessToken(string $accessToken): ?array
    {
        $clientId = self::clientId();
        if (!$clientId) {
            return null;
        }
        $info = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['access_token' => $accessToken]);
        if ($info->failed() || ($info->json('aud') !== $clientId && $info->json('azp') !== $clientId)) {
            return null;
        }
        $user = Http::timeout(10)->withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
        if ($user->failed() || empty($user->json('email')) || !$user->json('email_verified')) {
            return null;
        }

        return self::identity($user->json());
    }

    public static function clientId(): ?string
    {
        return config('services.google.client_id') ?: null;
    }

    private static function identity(array $p): array
    {
        return [
            'email' => strtolower(trim($p['email'])),
            'name' => $p['name'] ?? 'مستخدم Google',
            'google_id' => (string) $p['sub'],
            'avatar' => $p['picture'] ?? null,
        ];
    }
}
