<?php
declare(strict_types=1);

namespace LiberaPIX\controllers;

use LiberaPIX\Helpers;
use LiberaPIX\OrderRepository;
use LiberaPIX\MercadoPagoClient;

require_once __DIR__ . '/../../src/bootstrap.php';

// POST /api/checkout
// body: { product_id, amount_centavos, email? }
$data = Helpers::readJsonBody();
$productId = (string)($data['product_id'] ?? 'demo-product');
$amountCentavos = (int)($data['amount_centavos'] ?? 990);
$email = Helpers::sanitizeEmail($data['email'] ?? null);

try {
    $mp = new MercadoPagoClient();
    $desc = "LiberaPIX — pagamento do produto {$productId}";
    $resp = $mp->createPixPayment($amountCentavos, $desc, $email, 'Cliente');

    $qrCode = $resp['point_of_interaction']['transaction_data']['qr_code'] ?? '';
    $qrBase64 = $resp['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '';
    $paymentId = (string)($resp['id'] ?? '');

    if ($qrCode === '' || $qrBase64 === '') {
        throw new \RuntimeException('Resposta inesperada do Mercado Pago');
    }

    $repo = new OrderRepository();
    $orderId = $repo->create($productId, $amountCentavos, $email, $qrCode, $qrBase64);

    Helpers::json([
        'ok' => true,
        'order_id' => $orderId,
        'payment_id' => $paymentId,
        'qr_code' => $qrCode,
        'qr_code_base64' => $qrBase64,
        'copy_paste' => $qrCode
    ]);
} catch (\Throwable $e) {
    Helpers::json(['ok' => false, 'error' => $e->getMessage()], 500);
}
