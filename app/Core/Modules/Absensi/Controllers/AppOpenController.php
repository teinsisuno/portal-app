<?php

namespace App\Core\Modules\Absensi\Controllers;

use App\Core\Modules\Absensi\Services\AbsensiIntegrationService;
use App\Core\Modules\Apps\Models\AppModel;
use App\Core\Modules\Subscription\Models\Subscription;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Buka App" dari dashboard Central → redirect SSO ke aplikasi Absensi (FR-002).
 * Hanya user yang punya tenant + subscription Absensi aktif/trialing.
 */
class AppOpenController extends Controller
{
    public function __invoke(Request $request, string $slug, AbsensiIntegrationService $absensi): RedirectResponse
    {
        $app = AppModel::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        $tenant = $user->tenants()->first();
        if (! $tenant) {
            return redirect()->route('subscriptions.index')->withErrors(['tenant' => 'Kamu belum punya tenant.']);
        }

        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('app_id', $app->id)
            ->whereIn('status', ['trialing', 'active'])
            ->first();

        if (! $subscription) {
            return redirect()->route('subscriptions.index')->with('status', 'subscription-required');
        }

        // Mapping role: owner → owner; member/admin → admin (supervisor dibuat manual di app).
        $pivotRole = $tenant->users()->where('users.id', $user->id)->first()?->pivot->role;
        $absensiRole = $pivotRole === 'owner' ? 'owner' : 'admin';

        return redirect()->away($absensi->ssoUrl($tenant, $user, $absensiRole));
    }
}
