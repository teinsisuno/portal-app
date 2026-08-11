<?php

namespace App\Core\Modules\Tenant\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard tenant (FR-007): profil tenant, subscription list, riwayat pembayaran.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $tenant = $user->tenants()->first();

        $subscriptions = $tenant
            ? $tenant->subscriptions()->with('app', 'payments')->latest()->get()
            : collect();

        return view('dashboard', compact('tenant', 'subscriptions'));
    }
}
