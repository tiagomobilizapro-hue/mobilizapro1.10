<?php
declare(strict_types=1);

$startedAt = microtime(true);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Core/Database.php';
require_once __DIR__ . '/Core/Logger.php';
require_once __DIR__ . '/Core/Response.php';

$user = function_exists('mobi_current_user') ? mobi_current_user() : null;
if (!$user) {
    MobiResponse::fail('Sessão expirada. Faça login novamente.', 401);
}

$perfil = function_exists('mb_strtolower')
    ? mb_strtolower((string)($user['perfil'] ?? ''), 'UTF-8')
    : strtolower((string)($user['perfil'] ?? ''));

if (!in_array($perfil, ['gerencial', 'administrador', 'admin'], true)) {
    MobiResponse::fail('Acesso restrito ao nível gerencial/administrador.', 403);
}

$status = [
    'ok' => true,
    'app' => 'MobilizaPro',
    'product' => 'Enterprise Workforce Platform',
    'version' => '1.10 LTS',
    'channel' => 'health-check',
    'checks' => [],
];

try {
    $pdo = MobiDatabase::get();
    $dbStart = microtime(true);
    $pdo->query('SELECT 1')->fetchColumn();

    $status['checks']['database'] = [
        'ok' => true,
        'message' => 'Banco conectado',
        'latency_ms' => round((microtime(true) - $dbStart) * 1000, 2),
    ];
} catch (Throwable $e) {
    MobiLogger::error('Health check database failed', ['error' => $e->getMessage()]);
    $status['ok'] = false;
    $status['checks']['database'] = [
        'ok' => false,
        'message' => 'Falha na conexão com o banco',
    ];
}

$status['checks']['php'] = [
    'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'version' => PHP_VERSION,
];

$status['checks']['session'] = [
    'ok' => session_status() === PHP_SESSION_ACTIVE,
    'active' => session_status() === PHP_SESSION_ACTIVE,
];

$status['checks']['user'] = [
    'ok' => true,
    'nome' => $user['nome'] ?? null,
    'perfil' => $user['perfil'] ?? null,
];

$status['runtime'] = [
    'response_ms' => round((microtime(true) - $startedAt) * 1000, 2),
    'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
    'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
    'generated_at' => date('c'),
];

MobiResponse::json($status, $status['ok'] ? 200 : 500);
