<?php
declare(strict_types=1);

namespace LiberaPIX;

use PDO;

final class OrderRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(string $productId, int $amountCentavos, ?string $email, string $qrCode, string $qrBase64): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO orders (product_id, amount_centavos, email, status, qr_code, qr_code_base64, created_at)
            VALUES (:pid, :amt, :email, 'pending', :qr, :qr64, :now)");
        $stmt->execute([
            ':pid' => $productId,
            ':amt' => $amountCentavos,
            ':email' => $email,
            ':qr' => $qrCode,
            ':qr64' => $qrBase64,
            ':now' => gmdate('c'),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function markPaid(int $orderId, string $paymentId): void
    {
        $stmt = $this->pdo->prepare("UPDATE orders SET status='paid', mp_payment_id=:pid, paid_at=:now WHERE id=:id");
        $stmt->execute([
            ':pid' => $paymentId,
            ':now' => gmdate('c'),
            ':id' => $orderId,
        ]);
    }

    public function attachDownloadToken(int $orderId, string $token, \DateTimeImmutable $expiresAt): void
    {
        $stmt = $this->pdo->prepare("UPDATE orders SET download_token=:t, download_expires_at=:exp WHERE id=:id");
        $stmt->execute([
            ':t' => $token,
            ':exp' => $expiresAt->format(DATE_ATOM),
            ':id' => $orderId,
        ]);
    }

    public function find(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id=:id");
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByPaymentId(string $paymentId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE mp_payment_id=:pid");
        $stmt->execute([':pid' => $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE download_token=:t LIMIT 1");
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function saveWebhookLog(array $payload): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO webhook_log (received_at, raw_payload) VALUES (:now, :raw)");
        $stmt->execute([
            ':now' => gmdate('c'),
            ':raw' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function useIdempotencyKey(string $key): bool
    {
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO idempotency_keys (key, created_at) VALUES (:k, :now)");
        $stmt->execute([':k' => $key, ':now' => gmdate('c')]);
        // returns true if inserted (i.e., first time)
        return $stmt->rowCount() > 0;
    }
}
