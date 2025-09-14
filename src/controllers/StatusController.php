<?php
declare(strict_types=1);

namespace LiberaPIX\controllers;

use LiberaPIX\Helpers;
use LiberaPIX\OrderRepository;

require_once __DIR__ . '/../../src/bootstrap.php';

// GET /api/status?order_id=123
$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    Helpers::json(['ok' => false, 'error' => 'order_id inválido'], 400);
}

$repo = new OrderRepository();
$order = $repo->find($orderId);
if (!$order) {
    Helpers::json(['ok' => false, 'error' => 'Pedido não encontrado'], 404);
}

Helpers::json([
    'ok' => true,
    'status' => $order['status'],
    'download_token' => $order['download_token'],
    'download_expires_at' => $order['download_expires_at'],
]);
