<?php

namespace Tests\Feature;

use App\Core\Modules\Absensi\Support\SignedToken;
use App\Core\Modules\Apps\Models\AppModel;
use App\Core\Modules\Subscription\Models\Subscription;
use App\Core\Modules\Tenant\Models\Tenant;
use App\Core\Modules\Tenant\Models\TenantUser;
use App\Models\User;
use Database\Seeders\AppSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AbsensiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AppSeeder::class);

        config([
            'absensi.base_url' => 'http://absensi.test',
            'absensi.webhook_secret' => 'test-webhook-secret',
            'absensi.sso_secret' => 'test-sso-secret',
            'absensi.tenant_domain_pattern' => 'https://{slug}-absensi.megakomsel.com',
        ]);
    }

    // ─── Helper ────────────────────────────────────────────────

    protected function makeUserWithTenant(string $role = 'owner'): array
    {
        $user = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'PDAM Sejahtera',
            'slug' => 'pdam-sejahtera',
            'email' => $user->email,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $tenant];
    }

    protected function makeAbsensiSubscription(Tenant $tenant, string $status = 'trialing'): Subscription
    {
        $app = AppModel::where('slug', 'absensi')->firstOrFail();

        return Subscription::create([
            'tenant_id' => $tenant->id,
            'app_id' => $app->id,
            'plan' => 'monthly',
            'status' => $status,
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(7),
        ]);
    }

    // ─── FR-001: Provisioning webhook ──────────────────────────

    public function test_absensi_subscription_triggers_provisioning_webhook(): void
    {
        Http::fake([
            'absensi.test/*' => Http::response(['status' => 'queued'], 202),
        ]);

        [$user, $tenant] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'absensi')->firstOrFail();

        $this->actingAs($user)->post('/subscriptions', [
            'app_id' => $app->id,
            'plan' => 'monthly',
        ])->assertRedirect(route('subscriptions.index'));

        $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

        Http::assertSent(function ($request) use ($tenant, $subscription) {
            return $request->url() === 'http://absensi.test/api/v1/provisioning/tenant'
                && $request->hasHeader('X-Absensi-Webhook-Secret', 'test-webhook-secret')
                && $request['tenant_slug'] === $tenant->slug
                && $request['tenant_name'] === $tenant->name
                && $request['owner_email'] === $tenant->email
                && $request['subscription_id'] === (string) $subscription->id
                && $request['central_tenant_id'] === $tenant->id;
        });
    }

    public function test_non_absensi_subscription_does_not_trigger_webhook(): void
    {
        Http::fake();

        [$user] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'toyaa')->firstOrFail();

        $this->actingAs($user)->post('/subscriptions', [
            'app_id' => $app->id,
            'plan' => 'monthly',
        ])->assertRedirect(route('subscriptions.index'));

        Http::assertNothingSent();
    }

    // ─── FR-002: SSO redirect ──────────────────────────────────

    public function test_open_absensi_redirects_with_valid_sso_token(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $this->makeAbsensiSubscription($tenant, 'trialing');

        $response = $this->actingAs($user)->get('/apps/absensi/open');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://pdam-sejahtera-absensi.megakomsel.com/sso?token=', $location);

        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $payload = SignedToken::verify($query['token'], 'test-sso-secret');

        $this->assertNotNull($payload);
        $this->assertSame($tenant->slug, $payload['tenant_slug']);
        $this->assertSame($user->id, $payload['central_user_id']);
        $this->assertSame($user->email, $payload['email']);
        $this->assertSame('owner', $payload['role']);
    }

    public function test_open_absensi_member_gets_admin_role(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant('member');
        $this->makeAbsensiSubscription($tenant, 'active');

        $response = $this->actingAs($user)->get('/apps/absensi/open');

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $payload = SignedToken::verify($query['token'], 'test-sso-secret');
        $this->assertSame('admin', $payload['role']);
    }

    public function test_open_absensi_without_subscription_is_redirected_back(): void
    {
        [$user] = $this->makeUserWithTenant();

        $this->actingAs($user)->get('/apps/absensi/open')
            ->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('status', 'subscription-required');
    }

    public function test_open_absensi_rejects_inactive_subscription(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $this->makeAbsensiSubscription($tenant, 'canceled');

        $this->actingAs($user)->get('/apps/absensi/open')
            ->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('status', 'subscription-required');
    }

    public function test_open_absensi_requires_verified_email(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $this->makeAbsensiSubscription($tenant, 'trialing');

        // Simulasikan user yang email-nya belum diverifikasi
        // (email_verified_at bukan fillable — pakai forceFill)
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user)->get('/apps/absensi/open')
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-required');
    }

    public function test_open_non_absensi_app_is_blocked(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'toyaa')->firstOrFail();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'app_id' => $app->id,
            'plan' => 'monthly',
            'status' => 'trialing',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->actingAs($user)->get('/apps/toyaa/open')
            ->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('status', 'app-not-available');
    }
}
