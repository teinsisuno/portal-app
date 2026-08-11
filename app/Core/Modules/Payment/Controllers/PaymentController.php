<?php

namespace App\Core\Modules\Payment\Controllers;

use App\Core\Modules\Payment\Models\Payment;
use App\Core\Modules\Payment\Services\PaymentService;
use App\Core\Modules\Subscription\Models\Subscription;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    /**
     * Riwayat pembayaran tenant (FR-004).
     */
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenants()->first();

        $payments = $tenant
            ? Payment::with('subscription.app')->where('tenant_id', $tenant->id)->latest()->get()
            : collect();

        return view('payments.index', compact('payments', 'tenant'));
    }

    /**
     * Form upload bukti transfer (FR-004).
     */
    public function create(Request $request, Subscription $subscription): View|RedirectResponse
    {
        $tenant = $request->user()->tenants()->first();

        if (! $tenant || $subscription->tenant_id !== $tenant->id) {
            return redirect()->route('payments.index');
        }

        $amount = $this->paymentService->calculateAmount($subscription);

        return view('payments.create', [
            'subscription' => $subscription->load('app'),
            'amount' => $amount,
            'bank' => config('payment.bank'),
        ]);
    }

    /**
     * Simpan pembayaran + upload bukti (FR-004).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenant = $request->user()->tenants()->first();
        $subscription = Subscription::findOrFail($validated['subscription_id']);

        if (! $tenant || $subscription->tenant_id !== $tenant->id) {
            abort(403, 'Subscription bukan milik tenant kamu.');
        }

        $proofPath = $request->file('proof_image')->store('proofs', 'public');

        $this->paymentService->createPayment($subscription, 'manual_transfer', [
            'proof_image' => $proofPath,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('payments.index')->with('status', 'payment-submitted');
    }
}
