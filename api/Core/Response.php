<?php
declare(strict_types=1);

final class MobiResponse
{
    public static function json(array $payload, int $status = 200): never
    {
        if (function_exists('mobi_json')) {
            mobi_json($payload, $status);
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok(array $data = []): never
    {
        self::json(['ok' => true] + $data);
    }

    public static function fail(string $message, int $status = 400, array $extra = []): never
    {
        self::json(['ok' => false, 'message' => $message] + $extra, $status);
    }
}
