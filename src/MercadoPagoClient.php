<?php
declare(strict_types=1);

namespace LiberaPIX;

final class MercadoPagoClient
{
    private string $accessToken;

    public function __construct(?string $token = null)
    {
        $this->accessToken = $token ?? Config::mpAccessToken();
    }

    public function createPixPayment(int $amountCentavos, string $description, ?string $email = null, ?string $firstName = null): array
    {
        $amount = $amountCentavos / 100.0;
        $body = [
            'transaction_amount' => $amount,
            'description' => $description,
            'payment_method_id' => 'pix',
            'payer' => array_filter([
                'email' => $email,
                'first_name' => $firstName,
            ])
        ];

        return Helpers::httpPostJson(
            'https://api.mercadopago.com/v1/payments',
            $body,
            ['Authorization: Bearer ' . $this->accessToken]
        );
    }

    public function getPayment(string $paymentId): array
    {
        return Helpers::httpGetJson(
            'https://api.mercadopago.com/v1/payments/' . urlencode($paymentId),
            ['Authorization: Bearer ' . $this->accessToken]
        );
    }
}
