<?php
declare(strict_types=1);

namespace LiberaPIX\controllers;

use LiberaPIX\OrderRepository;
use LiberaPIX\MercadoPagoClient;
use LiberaPIX\TokenService;
use LiberaPIX\Config;

require_once __DIR__ . '/../../src/bootstrap.php';

// Webhook do Mercado Pago
// Pode chegar com diferentes formatos. Preferimos extrair o payment_id e consultar.
$repo = new OrderRepository();
$raw = file_get_contents('php://input') ?: '{}';
$payload = json_decode($raw, true) ?: [];
$repo->saveWebhookLog($payload);

$paymentId = null;

// Formatos comuns:
// 1) { "type":"payment", "data": { "id":"123456" } }
if (isset($payload['type']) && $payload['type'] === 'payment' && isset($payload['data']['id'])) {
    $paymentId = (string)$payload['data']['id'];
}

// 2) Query param "data.id" ou "id"
if (!$paymentId) {
    if (isset($_GET['data_id'])) $paymentId = (string)$_GET['data_id'];
    if (isset($_GET['id'])) $paymentId = (string)$_GET['id'];
}

if (!$paymentId) {
    http_response_code(202);
    echo 'accepted';
    exit;
}

// Idempotência básica por paymentId (insere e ignora se já visto)
$idemKey = 'mp-payment-' . $paymentId;
if (!$repo->useIdempotencyKey($idemKey)) {
    http_response_code(200);
    echo 'ok (duplicate)';
    exit;
}

// Consulta o pagamento no MP para verificar status
try {
    $mp = new MercadoPagoClient();
    $pmt = $mp->getPayment($paymentId);
    $status = $pmt['status'] ?? 'unknown';

    if ($status === 'approved') {
        // Encontrar pedido associado:
        // Neste boilerplate, associamos pelo valor/tempo; em produção,
        // você pode salvar o payment_id no momento do checkout se desejar.
        // Aqui faremos um mapeamento simples: como exemplo, assumimos que o
        // último pedido pendente será o correspondente se os times coincidirem.
        // Para algo robusto, salve o payment_id na criação do pedido.
        // Para o demo, tentaremos localizar pelo paymentId (se já salvo) ou
        // receberemos order_id em metadata.
        $orderId = null;

        // Se no pagamento veio metadata com order_id:
        if (isset($pmt['metadata']['order_id'])) {
            $orderId = (int)$pmt['metadata']['order_id'];
        }

        // Como fallback, tente achar por mp_payment_id (se alguém criou antes)
        if (!$orderId) {
            $order = $repo->findByPaymentId($paymentId);
            if ($order) $orderId = (int)$order['id'];
        }

        // Sem mapeamento direto? Em produção, salve o paymentId no checkout.
        // Para demo, não vamos falhar; apenas não marcamos nenhum pedido.
        if ($orderId) {
            $repo->markPaid($orderId, $paymentId);
            $token = TokenService::generate(32);
            $exp = TokenService::expirationFromNow(Config::tokenTtlHours());
            $repo->attachDownloadToken($orderId, $token, $exp);
        }
    }

    http_response_code(200);
    echo 'ok';
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'error';
}
