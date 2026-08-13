<?php

namespace App\Core\Modules\Payment\Services;

use App\Core\Modules\Absensi\Services\AbsensiIntegrationService;
use App\Core\Modules\Payment\Gateways\ManualTransferGateway;
use App\Core\Modules\Payment\Gateways\PaymentGatewayInterface;
use App\Core\Modules\Payment\Models\Payment;
use App\Core\Modules\Subscription\Models\Subscription;
use App\Core\Modules\Tenant\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Buat pembayaran pending untuk subscription (FR-004).
     *
     * @param  array<string, mixed>  $extra  data tambahan (proof_image, notes)
     */
    public function createPayment(Subscription $subscription, string $method = 'manual_transfer', array $extra = []): Payment
    {
        $gateway = $this->resolveGateway();

        $amount = $this->calculateAmount($subscription);

        $gatewayResult = $gateway->createPayment([
            'amount' => $amount,
            'tenant' => $subscription->tenant->slug,
            'app' => $subscription->app->slug,
        ]);

        return Payment::create(array_merge([
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'amount' => $amount,
            'method' => $method,
            'status' => 'pending',
            'gateway_ref' => $gatewayResult['gateway_ref'] ?? null,
        ], $extra));
    }

    /**
     * Konfirmasi pembayaran (idempotent) → subscription active, tenant active.
     * Semua update dibungkus transaction; ends_at dihitung dari plan
     * (monthly = +1 bulan, yearly = +1 tahun sejak konfirmasi).
     */
    public function confirmPayment(Payment $payment, int $adminId, ?string $notes = null): bool
    {
        // Idempotent: konfirmasi 2x tidak dobel (PRD §8 Reliability).
        if ($payment->status === 'confirmed') {
            return false;
        }

        if ($payment->status !== 'pending') {
            return false;
        }

        DB::transaction(function () use ($payment, $adminId, $notes) {
            $payment->update([
                'status' => 'confirmed',
                'confirmed_by' => $adminId,
                'confirmed_at' => now(),
                'notes' => $notes,
            ]);

            $subscription = $payment->subscription;

            $subscription->update([
                'status' => 'active',
                'starts_at' => $subscription->starts_at ?? now(),
                'ends_at' => $subscription->plan === 'yearly' ? now()->addYear() : now()->addMonth(),
            ]);

            Tenant::where('id', $payment->tenant_id)->update(['status' => 'active']);

            // MED-5: retry provisioning bila webhook pertama (saat subscribe) gagal.
            // Absensi-app idempotent — aman dipanggil berulang.
            if ($subscription->app->slug === 'absensi') {
                app(AbsensiIntegrationService::class)->provisionTenant($subscription);
            }
        });

        return true;
    }

    /**
     * Tolak pembayaran (idempotent).
     */
    public function rejectPayment(Payment $payment, int $adminId, ?string $notes = null): bool
    {
        if (! in_array($payment->status, ['pending', 'failed'])) {
            return false;
        }

        $payment->update([
            'status' => 'rejected',
            'confirmed_by' => $adminId,
            'confirmed_at' => now(),
            'notes' => $notes,
        ]);

        return true;
    }

    /**
     * Hitung nominal: bulanan = price_monthly; tahunan = price_monthly × 12.
     */
    public function calculateAmount(Subscription $subscription): float
    {
        $price = (float) $subscription->app->price_monthly;

        return $subscription->plan === 'yearly' ? round($price * 12, 2) : $price;
    }

    /**
     * Resolve gateway aktif dari config/payment.php.
     */
    protected function resolveGateway(): PaymentGatewayInterface
    {
        $default = config('payment.default', 'manual_transfer');
        $class = config("payment.gateways.{$default}.class", ManualTransferGateway::class);

        $gateway = new $class;

        if (! $gateway instanceof PaymentGatewayInterface) {
            Log::error("Payment gateway [{$class}] tidak implement PaymentGatewayInterface");

            throw new \RuntimeException("Gateway [{$default}] tidak valid.");
        }

        return $gateway;
    }
}
