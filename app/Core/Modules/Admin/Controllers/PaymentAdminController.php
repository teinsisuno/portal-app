<?php

namespace App\Core\Modules\Admin\Controllers;

use App\Core\Modules\Payment\Models\Payment;
use App\Core\Modules\Payment\Services\PaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentAdminController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    /**
     * Daftar pembayaran, default urut pending dulu (FR-006).
     */
    public function index(Request $request): View
    {
        $query = Payment::with('tenant', 'subscription.app')->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('admin.payments.index', [
            'payments' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Konfirmasi pembayaran → subscription active (idempotent).
     */
    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        $this->paymentService->confirmPayment($payment, $request->user()->id);

        return back()->with('status', 'payment-confirmed');
    }

    /**
     * Tolak pembayaran (idempotent).
     */
    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $this->paymentService->rejectPayment(
            $payment,
            $request->user()->id,
            $request->input('notes'),
        );

        return back()->with('status', 'payment-rejected');
    }
}
