<?php

namespace App\Core\Modules\Payment\Gateways;

/**
 * Abstraction payment gateway (FR-005).
 * Implementasi: ManualTransferGateway (aktif), MidtransGateway (nanti).
 */
interface PaymentGatewayInterface
{
    /**
     * Buat transaksi pembayaran di gateway.
     *
     * @return array<string, mixed> { gateway_ref, instructions?, amount, ... }
     */
    public function createPayment(array $data): array;

    /**
     * Cek status transaksi di gateway.
     *
     * @return array<string, mixed> { status: pending|confirmed|failed, ... }
     */
    public function checkStatus(string $reference): array;

    /**
     * Proses webhook dari gateway.
     *
     * @return array<string, mixed>
     */
    public function handleWebhook(array $payload): array;
}
