<?php
declare(strict_types=1);

namespace LiberaPIX;

final class Helpers
{
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function httpPostJson(string $url, array $body, array $headers = []): array
    {
        $ch = curl_init($url);
        $payload = json_encode($body, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $headers = array_merge(['Content-Type: application/json'], $headers);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            throw new \RuntimeException('HTTP error: ' . curl_error($ch));
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($resp, true);
        if ($status >= 400) {
            throw new \RuntimeException('HTTP ' . $status . ' ' . $resp);
        }
        return is_array($data) ? $data : [];
    }

    public static function httpGetJson(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            throw new \RuntimeException('HTTP error: ' . curl_error($ch));
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($resp, true);
        if ($status >= 400) {
            throw new \RuntimeException('HTTP ' . $status . ' ' . $resp);
        }
        return is_array($data) ? $data : [];
    }

    public static function sanitizeEmail(?string $email): ?string
    {
        if (!$email) return null;
        $email = trim($email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ?: null;
    }
}
