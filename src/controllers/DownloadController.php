<?php
declare(strict_types=1);

namespace LiberaPIX\controllers;

use LiberaPIX\OrderRepository;
use LiberaPIX\TokenService;

require_once __DIR__ . '/../../src/bootstrap.php';

// GET /download.php?token=XXXX
$token = isset($_GET['token']) ? (string)$_GET['token'] : '';
if ($token === '') {
    http_response_code(400);
    echo 'Token ausente';
    exit;
}

$repo = new OrderRepository();
$order = $repo->findByToken($token);
if (!$order) {
    http_response_code(404);
    echo 'Token inválido';
    exit;
}

if ($order['status'] !== 'paid') {
    http_response_code(403);
    echo 'Pedido ainda não pago';
    exit;
}

if (TokenService::isExpired($order['download_expires_at'])) {
    http_response_code(410);
    echo 'Token expirado';
    exit;
}

// Arquivo de demonstração
$filePath = __DIR__ . '/../../storage/products/exemplo.txt';
if (!is_file($filePath)) {
    file_put_contents($filePath, "Obrigado pela compra! Este é um arquivo de exemplo protegido pelo LiberaPIX.\n");
}

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="exemplo.txt"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
