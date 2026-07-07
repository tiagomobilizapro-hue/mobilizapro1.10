<?php
declare(strict_types=1);

/**
 * MobilizaPro Enterprise Workforce Platform
 * Painel de Saúde Administrativo
 *
 * URL:
 * /api/health-panel.php
 *
 * Este arquivo não altera banco, não altera dados e não altera o visual operacional.
 */

$startedAt = microtime(true);

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Core/Database.php';
require_once __DIR__ . '/Core/Logger.php';

$user = function_exists('mobi_current_user') ? mobi_current_user() : null;

if (!$user) {
    http_response_code(401);
    echo '<h1>MobilizaPro</h1><p>Sessão expirada. Faça login novamente.</p>';
    exit;
}

$perfil = function_exists('mb_strtolower')
    ? mb_strtolower((string)($user['perfil'] ?? ''), 'UTF-8')
    : strtolower((string)($user['perfil'] ?? ''));

if (!in_array($perfil, ['gerencial', 'administrador', 'admin'], true)) {
    http_response_code(403);
    echo '<h1>MobilizaPro</h1><p>Acesso restrito ao nível gerencial/administrador.</p>';
    exit;
}

$checks = [];
$overallOk = true;

try {
    $pdo = MobiDatabase::get();
    $dbStart = microtime(true);
    $pdo->query('SELECT 1')->fetchColumn();
    $checks['Banco de Dados'] = [
        'ok' => true,
        'value' => 'Conectado',
        'detail' => round((microtime(true) - $dbStart) * 1000, 2) . ' ms',
    ];
} catch (Throwable $e) {
    $overallOk = false;
    MobiLogger::error('Health panel database failed', ['error' => $e->getMessage()]);
    $checks['Banco de Dados'] = [
        'ok' => false,
        'value' => 'Falha',
        'detail' => 'Verifique config/config.php e disponibilidade do MySQL.',
    ];
}

$checks['PHP'] = [
    'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'value' => PHP_VERSION,
    'detail' => 'Compatibilidade mínima: PHP 8.1',
];

$checks['Sessão'] = [
    'ok' => session_status() === PHP_SESSION_ACTIVE,
    'value' => session_status() === PHP_SESSION_ACTIVE ? 'Ativa' : 'Inativa',
    'detail' => 'Usuário: ' . htmlspecialchars((string)($user['nome'] ?? $user['cpf'] ?? 'Não identificado'), ENT_QUOTES, 'UTF-8'),
];

$logDir = dirname(__DIR__) . '/storage/logs';
$checks['Logs'] = [
    'ok' => is_dir($logDir) && is_writable($logDir),
    'value' => is_dir($logDir) ? 'Diretório existente' : 'Diretório ausente',
    'detail' => is_writable($logDir) ? 'Gravável' : 'Não gravável ou ainda não criado',
];

$checks['Memória'] = [
    'ok' => true,
    'value' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
    'detail' => 'Pico: ' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
];

$checks['Servidor'] = [
    'ok' => true,
    'value' => date('d/m/Y H:i:s'),
    'detail' => $_SERVER['SERVER_SOFTWARE'] ?? 'Servidor web',
];

$responseMs = round((microtime(true) - $startedAt) * 1000, 2);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MobilizaPro - Health Panel</title>
    <style>
        :root {
            --bg: #070d18;
            --panel: #101a2b;
            --panel2: #0d1626;
            --border: #243753;
            --text: #f4f7fb;
            --muted: #9fb0c8;
            --blue: #1f7aff;
            --green: #22c55e;
            --red: #ef4444;
            --yellow: #facc15;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: radial-gradient(circle at top left, #0d274d 0, var(--bg) 42%);
            color: var(--text);
            padding: 32px;
        }
        .wrap {
            max-width: 1180px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 24px;
        }
        .brand h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: .2px;
        }
        .brand p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
        }
        .badge {
            border: 1px solid var(--border);
            background: rgba(31,122,255,.12);
            color: #8dc0ff;
            padding: 10px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
        }
        .status {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 22px;
            background: linear-gradient(180deg, rgba(16,26,43,.96), rgba(8,15,28,.96));
            margin-bottom: 22px;
        }
        .status strong {
            font-size: 22px;
        }
        .status.ok strong { color: var(--green); }
        .status.fail strong { color: var(--red); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }
        .card {
            border: 1px solid var(--border);
            background: rgba(13,22,38,.94);
            border-radius: 16px;
            padding: 18px;
            min-height: 140px;
        }
        .card .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .dot {
            width: 14px;
            height: 14px;
            border-radius: 999px;
            background: var(--red);
            box-shadow: 0 0 16px rgba(239,68,68,.55);
        }
        .dot.ok {
            background: var(--green);
            box-shadow: 0 0 16px rgba(34,197,94,.55);
        }
        .card h2 {
            margin: 0;
            font-size: 15px;
            color: #dce8ff;
            text-transform: uppercase;
            letter-spacing: .6px;
        }
        .value {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .detail {
            color: var(--muted);
            line-height: 1.45;
            font-size: 13px;
        }
        .footer {
            margin-top: 22px;
            color: var(--muted);
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        a {
            color: #8dc0ff;
            text-decoration: none;
        }
    </style>
</head>
<body>
<main class="wrap">
    <section class="header">
        <div class="brand">
            <h1>MobilizaPro</h1>
            <p>Enterprise Workforce Platform · Health Panel Administrativo</p>
        </div>
        <div class="badge">Versão 1.10 LTS</div>
    </section>

    <section class="status <?php echo $overallOk ? 'ok' : 'fail'; ?>">
        <strong><?php echo $overallOk ? 'Sistema saudável' : 'Atenção necessária'; ?></strong>
        <p class="detail">
            Painel de diagnóstico técnico para uso gerencial. Este painel não altera dados e não executa rotinas de manutenção.
        </p>
    </section>

    <section class="grid">
        <?php foreach ($checks as $name => $check): ?>
            <article class="card">
                <div class="top">
                    <h2><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <span class="dot <?php echo !empty($check['ok']) ? 'ok' : ''; ?>"></span>
                </div>
                <div class="value"><?php echo htmlspecialchars((string)$check['value'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="detail"><?php echo htmlspecialchars((string)$check['detail'], ENT_QUOTES, 'UTF-8'); ?></div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="footer">
        <span>Resposta: <?php echo htmlspecialchars((string)$responseMs, ENT_QUOTES, 'UTF-8'); ?> ms</span>
        <span>Usuário: <?php echo htmlspecialchars((string)($user['nome'] ?? $user['cpf'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span><a href="health.php">Ver JSON técnico</a></span>
    </section>
</main>
</body>
</html>
