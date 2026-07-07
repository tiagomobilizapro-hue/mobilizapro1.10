<?php
declare(strict_types=1);

final class MobiDatabase
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo instanceof PDO) return self::$pdo;

        if (function_exists('mobi_pdo')) {
            self::$pdo = mobi_pdo();
            return self::$pdo;
        }

        $configPath = dirname(__DIR__, 2) . '/config/config.php';
        if (!is_file($configPath)) {
            throw new RuntimeException('Arquivo config/config.php não encontrado.');
        }

        $config = require $configPath;
        $db = $config['db'] ?? [];

        $host = (string)($db['host'] ?? 'localhost');
        $name = (string)($db['name'] ?? $db['nome'] ?? '');
        $user = (string)($db['user'] ?? $db['usuario'] ?? '');
        $pass = (string)($db['pass'] ?? '');
        $charset = (string)($db['charset'] ?? 'utf8mb4');

        if ($name === '' || $user === '') {
            throw new RuntimeException('Configuração de banco incompleta.');
        }

        self::$pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset={$charset}",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$pdo;
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::get();

        if ($pdo->inTransaction()) {
            return $callback($pdo);
        }

        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
