<?php

namespace App\Core\Modules\Payment\Gateways;

use Illuminate\Support\Str;

/**
 * Gateway manual transfer: rekening tujuan + konfirmasi admin (FR-004).
 */
class ManualTransferGateway implements PaymentGatewayInterface
{
    public function createPayment(array $data): array
    {
        $reference = 'MT-'.strtoupper(Str::random(10));

        return [
            'gateway_ref' => $reference,
            'instructions' => [
                'bank_name' => config('payment.bank.bank_name'),
                'account_number' => config('payment.bank.account_number'),
                'account_name' => config('payment.bank.account_name'),
            ],
            'status' => 'pending',
        ];
    }

    public function checkStatus(string $reference): array
    {
        // Manual transfer: status ditentukan admin (tidak ada auto-check).
        return ['status' => 'pending'];
    }

    public function handleWebhook(array $payload): array
    {
        // Manual transfer tidak memakai webhook.
        return ['handled' => false];
    }
}
