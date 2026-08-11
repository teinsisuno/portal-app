<?php

namespace App\Core\Modules\Absensi\Services;

use App\Core\Modules\Absensi\Support\SignedToken;
use App\Core\Modules\Subscription\Models\Subscription;
use App\Core\Modules\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Integrasi Central ↔ Absensi (produk pertama megakomsel.com).
 * - provisionTenant(): webhook provisioning 1 tenant = 1 DB (FR-001)
 * - ssoUrl(): signed token SSO short-lived + redirect URL (FR-002)
 */
class AbsensiIntegrationService
{
    /**
     * Kirim webhook provisioning ke absensi-app saat subscription Absensi aktif.
     * Absensi-app bersifat idempotent (tidak membuat DB dobel).
     */
    public function provisionTenant(Subscription $subscription): void
    {
        $baseUrl = rtrim((string) config('absensi.base_url'), '/');
        $secret = (string) config('absensi.webhook_secret');

        if ($baseUrl === '' || $secret === '') {
            Log::warning('Absensi integrasi belum dikonfigurasi (ABSENSI_BASE_URL / ABSENSI_WEBHOOK_SECRET)', [
                'subscription_id' => $subscription->id,
            ]);

            return;
        }

        $tenant = $subscription->tenant;
        $owner = $tenant->users()->wherePivot('role', 'owner')->first();

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-Absensi-Webhook-Secret' => $secret])
                ->post($baseUrl.'/api/v1/provisioning/tenant', [
                    'tenant_slug' => $tenant->slug,
                    'tenant_name' => $tenant->name,
                    'owner_email' => $owner?->email ?? $tenant->email,
                    'subscription_id' => (string) $subscription->id,
                    'central_tenant_id' => (int) $tenant->id,
                ]);

            Log::info('Absensi provisioning response', [
                'subscription_id' => $subscription->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        } catch (Throwable $e) {
            Log::error('Absensi provisioning gagal', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bangun URL redirect SSO ke aplikasi Absensi (FR-002).
     * Token: signed HMAC, exp < 60 detik, one-time (jti).
     */
    public function ssoUrl(Tenant $tenant, User $user, string $role = 'owner'): string
    {
        $token = SignedToken::sign([
            'tenant_slug' => $tenant->slug,
            'central_user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
        ], (string) config('absensi.sso_secret'));

        $pattern = (string) config('absensi.tenant_domain_pattern', '{slug}-absensi.megakomsel.com');
        $domain = str_replace('{slug}', $tenant->slug, $pattern);

        return "https://{$domain}/sso?token=".urlencode($token);
    }
}
