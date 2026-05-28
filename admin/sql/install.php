<?php
/**
 * One-time database installer.
 * Browser: /admin/sql/install.php
 * CLI:     php admin/sql/install.php
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('Install can only be run from localhost.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

function out(string $message): void
{
    echo $message . PHP_EOL;
}

function runSqlFile(PDO $pdo, string $path, string $label): void
{
    if (!is_readable($path)) {
        throw new RuntimeException("SQL file not found: {$path}");
    }

    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException("SQL file is empty: {$path}");
    }

    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }

    out("[OK] {$label}");
}

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $host = $dbConfig['host'] ?? '127.0.0.1';
    $port = (int) ($dbConfig['port'] ?? 3306);
    $database = $dbConfig['database'] ?? 'case_management';
    $username = $dbConfig['username'] ?? 'root';
    $password = $dbConfig['password'] ?? '';
    $charset = $dbConfig['charset'] ?? 'utf8mb4';

    out('Connecting to MySQL...');

    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset),
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    out("Creating database `{$database}` if needed...");
    $pdo->exec(
        sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '``', $database)
        )
    );
    $pdo->exec('USE `' . str_replace('`', '``', $database) . '`');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    runSqlFile($pdo, __DIR__ . '/schema.sql', 'Schema imported');
    runSqlFile($pdo, __DIR__ . '/seed.sql', 'Seed data imported');

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    out('');
    out('Database setup complete.');
    out('Admin login: admin@admin.com / admin123');
    out('Login URL: /admin/auth/login.php');
} catch (Throwable $e) {
    out('');
    out('[ERROR] ' . $e->getMessage());
    exit(1);
}
