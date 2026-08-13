<?php

namespace Tests\Feature;

use App\Core\Modules\Apps\Models\AppModel;
use App\Core\Modules\Payment\Models\Payment;
use App\Core\Modules\Subscription\Models\Subscription;
use App\Core\Modules\Tenant\Models\Tenant;
use App\Core\Modules\Tenant\Models\TenantUser;
use App\Models\User;
use Database\Seeders\AppSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CentralPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AppSeeder::class);
    }

    // ─── Helper ────────────────────────────────────────────────

    protected function makeUserWithTenant(array $userAttrs = []): array
    {
        $user = User::factory()->create($userAttrs);

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
            'role' => 'owner',
        ]);

        return [$user, $tenant];
    }

    protected function makeSubscription(Tenant $tenant, AppModel $app): Subscription
    {
        return Subscription::create([
            'tenant_id' => $tenant->id,
            'app_id' => $app->id,
            'plan' => 'monthly',
            'status' => 'past_due',
            'starts_at' => now()->subMonth(),
        ]);
    }

    // ─── FR-001: Registrasi ────────────────────────────────────

    public function test_register_creates_user_tenant_and_owner_pivot(): void
    {
        $response = $this->post('/register', [
            'name' => 'PDAM Sejahtera',
            'email' => 'pdam@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'pdam@example.test']);
        $this->assertDatabaseHas('tenants', [
            'slug' => 'pdam-sejahtera',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('tenant_user', ['role' => 'owner']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dupe@example.test']);

        $this->post('/register', [
            'name' => 'Dup',
            'email' => 'dupe@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('email');
    }

    // ─── FR-002/003: Katalog & Langganan ───────────────────────

    public function test_app_catalog_lists_seeded_apps(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/apps')
            ->assertOk()
            ->assertSee('Toyaa')
            ->assertSee('Kasir UMKM')
            ->assertSee('Laundry');
    }

    public function test_user_can_subscribe_to_available_app(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'toyaa')->firstOrFail();

        $this->actingAs($user)->post('/subscriptions', [
            'app_id' => $app->id,
            'plan' => 'monthly',
        ])->assertRedirect(route('subscriptions.index'));

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'app_id' => $app->id,
            'status' => 'trialing',
        ]);
    }

    public function test_duplicate_active_subscription_is_rejected(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'toyaa')->firstOrFail();
        $this->makeSubscription($tenant, $app)->update(['status' => 'active']);

        $this->actingAs($user)->post('/subscriptions', [
            'app_id' => $app->id,
            'plan' => 'monthly',
        ])->assertSessionHas('status', 'subscription-exists');

        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_user_cannot_subscribe_to_coming_soon_app(): void
    {
        [$user] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'laundry')->firstOrFail();
        $this->assertSame('coming_soon', $app->status);

        $this->actingAs($user)->post('/subscriptions', [
            'app_id' => $app->id,
            'plan' => 'monthly',
        ])->assertSessionHasErrors('app_id');

        $this->assertDatabaseCount('subscriptions', 0);
    }

    // ─── FR-004: Pembayaran manual ─────────────────────────────

    public function test_user_can_upload_payment_proof(): void
    {
        Storage::fake('public');

        [$user, $tenant] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'toyaa')->firstOrFail();
        $subscription = $this->makeSubscription($tenant, $app);

        $this->actingAs($user)->post('/payments', [
            'subscription_id' => $subscription->id,
            'proof_image' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('payments.index'));

        $this->assertDatabaseHas('payments', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'status' => 'pending',
            'method' => 'manual_transfer',
            'amount' => 50000,
        ]);

        $payment = Payment::firstOrFail();
        Storage::disk('public')->assertExists($payment->proof_image);
    }

    public function test_user_cannot_pay_other_tenant_subscription(): void
    {
        Storage::fake('public');

        [$user, $tenant] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'toyaa')->firstOrFail();
        $subscription = $this->makeSubscription($tenant, $app);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)->post('/payments', [
            'subscription_id' => $subscription->id,
            'proof_image' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payments_create_blocked_for_trialing_subscription(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'toyaa')->firstOrFail();
        $this->makeSubscription($tenant, $app)->update(['status' => 'trialing']);

        $sub = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)->get("/payments/create/{$sub->id}")
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('status', 'subscription-active');
    }

    public function test_subscriptions_expire_command_transitions_past_due(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $toyaa = AppModel::where('slug', 'toyaa')->firstOrFail();
        $absensi = AppModel::where('slug', 'absensi')->firstOrFail();
        $laundry = AppModel::where('slug', 'laundry')->firstOrFail();

        // Trial lewat jatuh tempo → past_due
        $trial = Subscription::create([
            'tenant_id' => $tenant->id,
            'app_id' => $toyaa->id,
            'plan' => 'monthly',
            'status' => 'trialing',
            'starts_at' => now()->subDays(10),
            'trial_ends_at' => now()->subDays(3),
        ]);

        // Active lewat ends_at → past_due
        $active = Subscription::create([
            'tenant_id' => $tenant->id,
            'app_id' => $absensi->id,
            'plan' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDays(1),
        ]);

        // Subscription yang masih jalan tidak boleh kena
        Subscription::create([
            'tenant_id' => $tenant->id,
            'app_id' => $laundry->id,
            'plan' => 'monthly',
            'status' => 'trialing',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('past_due', $trial->fresh()->status);
        $this->assertSame('past_due', $active->fresh()->status);
        $this->assertSame('trialing', Subscription::where('app_id', $laundry->id)->first()->status);
    }

    // ─── FR-006: Admin panel ───────────────────────────────────

    public function test_non_admin_cannot_access_admin_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/tenants')->assertForbidden();
        $this->actingAs($user)->get('/admin/payments')->assertForbidden();
        $this->actingAs($user)->get('/admin/apps')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_confirm_payment_and_activate_subscription(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'toyaa')->firstOrFail();
        $subscription = $this->makeSubscription($tenant, $app);

        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'amount' => 50000,
            'method' => 'manual_transfer',
            'status' => 'pending',
            'gateway_ref' => 'MT-TEST123',
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post("/admin/payments/{$payment->id}/confirm")
            ->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'active']);
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'active']);

        // HIGH-1/MED-8: ends_at dihitung dari plan (monthly = +1 bulan sejak konfirmasi).
        $this->assertNotNull($subscription->fresh()->ends_at);
        $this->assertTrue($subscription->fresh()->ends_at->greaterThan(now()->addDays(27)));
        $this->assertTrue($subscription->fresh()->ends_at->lessThanOrEqualTo(now()->addMonths(1)));

        // Idempotent: konfirmasi ulang tidak mengubah apa-apa (PRD §8)
        $this->actingAs($admin)->post("/admin/payments/{$payment->id}/confirm");
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'confirmed', 'confirmed_at' => $payment->fresh()->confirmed_at]);
    }

    public function test_admin_can_reject_payment(): void
    {
        [$user, $tenant] = $this->makeUserWithTenant();
        $app = AppModel::where('slug', 'toyaa')->firstOrFail();
        $subscription = $this->makeSubscription($tenant, $app);

        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'amount' => 50000,
            'method' => 'manual_transfer',
            'status' => 'pending',
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post("/admin/payments/{$payment->id}/reject", [
            'notes' => 'Bukti tidak jelas',
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'rejected']);
    }

    public function test_admin_cannot_create_duplicate_app_name(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/apps', [
            'name' => 'Absensi',
            'description' => 'Dobel',
            'price_monthly' => 10000,
            'status' => 'available',
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('apps', 4); // 4 dari AppSeeder
    }

    // ─── PRD §11: API Auth ─────────────────────────────────────

    public function test_api_register_returns_token_and_tenant(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'API PDAM',
            'email' => 'api@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user', 'tenant'])
            ->assertJsonPath('tenant.status', 'pending');
    }

    public function test_api_login_returns_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_api_login_unverified_email_is_blocked(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertStatus(403)->assertJsonPath('message', 'Email belum diverifikasi. Cek inbox untuk link verifikasi.');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_api_login_wrong_password_rejected(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'salah123',
        ])->assertStatus(422);
    }

    public function test_api_protected_route_requires_token(): void
    {
        $this->postJson('/api/v1/auth/resend-verification')->assertStatus(401);
    }

    public function test_api_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_revoked_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $user->tokens()->delete();

        $this->withToken($token)->postJson('/api/v1/auth/resend-verification')->assertStatus(401);
    }
}
