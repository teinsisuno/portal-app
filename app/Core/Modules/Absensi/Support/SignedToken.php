<?php

namespace App\Core\Modules\Absensi\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Signed token HMAC-SHA256 (share secret dengan absensi-app).
 * Format: base64url(payload).base64url(hmac)
 * payload: array assoc {tenant_slug, central_user_id, name, email, role, exp, jti}
 * One-time use via cache (jti), short-lived (exp).
 *
 * Harus kompatibel 1:1 dengan App\Support\SignedToken di absensi-app.
 */
class SignedToken
{
    public static function sign(array $payload, string $secret, int $ttlSeconds = 60): string
    {
        $payload['jti'] = $payload['jti'] ?? bin2hex(random_bytes(16));
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;

        $encoded = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $encoded, $secret, true));

        return $encoded.'.'.$signature;
    }

    /**
     * @return array|null payload jika valid & belum terpakai, null jika invalid/expired/used
     */
    public static function verify(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$encoded, $signature] = $parts;

        $expected = self::base64UrlEncode(hash_hmac('sha256', $encoded, $secret, true));
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($encoded), true);
        if (! is_array($payload) || empty($payload['jti'])) {
            return null;
        }

        // one-time use
        $cacheKey = 'sso_token_used:'.$payload['jti'];
        if (Cache::has($cacheKey)) {
            return null;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        // tandai terpakai selama masa valid + buffer (2x ttl)
        Cache::put($cacheKey, true, now()->addSeconds(($payload['exp'] - $payload['iat']) * 2));

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
