<?php
declare(strict_types=1);

$root = dirname(__DIR__);

if (is_file("$root/vendor/autoload.php")) {
    require "$root/vendor/autoload.php";
}
spl_autoload_register(fn(string $c) => is_file($f = "$root/src/$c.php") ? require $f : null);

foreach (@file("$root/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line !== '' && $line[0] !== '#' && str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\"'");
    }
}

function env(string $key, ?string $default = null): ?string
{
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

function db(): PDO
{
    static $pdo;
    return $pdo ??= new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST', '127.0.0.1'),
            env('DB_PORT', '3306'),
            env('DB_NAME', 'invitations')
        ),
        env('DB_USER', 'root'),
        env('DB_PASS', ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function adminKeyOk(?string $given): bool
{
    $key = env('ADMIN_KEY');
    return $key !== null && $given !== null && hash_equals($key, $given);
}
