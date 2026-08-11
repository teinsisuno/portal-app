<?php

namespace App\Core\Modules\Subscription\Controllers;

use App\Core\Modules\Subscription\Models\Subscription;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    /**
     * Daftar langganan tenant user (FR-003).
     */
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenants()->first();

        $subscriptions = $tenant
            ? $tenant->subscriptions()->with('app', 'payments')->latest()->get()
            : collect();

        return view('subscriptions.index', compact('tenant', 'subscriptions'));
    }

    /**
     * Buat langganan baru: status trialing 7 hari (FR-003).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_id' => ['required', 'exists:apps,id'],
            'plan' => ['required', 'in:monthly,yearly'],
        ]);

        $tenant = $request->user()->tenants()->first();

        if (! $tenant) {
            return back()->withErrors(['tenant' => 'Kamu belum punya tenant.']);
        }

        $exists = Subscription::where('tenant_id', $tenant->id)
            ->where('app_id', $validated['app_id'])
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->exists();

        if ($exists) {
            return back()->with('status', 'subscription-exists');
        }

        $now = now();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'app_id' => $validated['app_id'],
            'plan' => $validated['plan'],
            'status' => 'trialing',
            'trial_ends_at' => $now->copy()->addDays(7),
            'starts_at' => $now,
        ]);

        return redirect()->route('subscriptions.index')->with('status', 'subscription-created');
    }
}
