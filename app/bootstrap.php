<?php
declare(strict_types=1);

const ROOT = __DIR__ . '/..';
const DUMMY_HASH = '$2y$12$aa5HG2TNj.M2Paf7V0va2ueCDuOM8XmGGmQxQ5ScMN4pKrsq1F6IS';
$configuration = ROOT . '/config.php';
$config = is_file($configuration) ? require $configuration : [];
$config += [
    'storage' => ROOT . '/storage',
    'origin' => 'https://www.axisinvestmentgrp.com',
    'local' => PHP_SAPI === 'cli-server',
];

function db(): PDO
{
    static $db;
    if ($db) {
        return $db;
    }
    global $config;
    $storage = $config['storage'];
    if (!is_dir($storage) && !mkdir($storage, 0700, true) && !is_dir($storage)) {
        throw new RuntimeException('The private storage directory could not be created.');
    }
    $storage = realpath($storage);
    $public = realpath(($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ROOT . '/public');
    if ($storage === false || $public === false) {
        throw new RuntimeException('The storage or document root could not be resolved.');
    }
    $storagePath = str_replace('\\', '/', strtolower($storage)) . '/';
    $publicPath = str_replace('\\', '/', strtolower($public)) . '/';
    if (str_starts_with($storagePath, $publicPath)) {
        throw new RuntimeException('Storage must be outside the document root.');
    }
    $db = new PDO('sqlite:' . $storage . '/axis.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('PRAGMA busy_timeout = 5000; PRAGMA foreign_keys = ON;');
    $db->exec(file_get_contents(__DIR__ . '/schema.sql'));
    return $db;
}

function query(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function field(string $key, string $default = ''): string
{
    return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : $default;
}

function param(string $key): string
{
    return is_string($_GET[$key] ?? null) ? $_GET[$key] : '';
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function session(): void
{
    global $config;
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name($config['local'] ? 'axis_local' : '__Host-axis');
    session_set_cookie_params(['path' => '/', 'secure' => !$config['local'], 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
    header('Cache-Control: no-store');
    if (isset($_SESSION['touched']) && time() - $_SESSION['touched'] > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['touched'] = time();
}

function csrf(): string
{
    session();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function checkCsrf(): void
{
    session();
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], field('csrf'))) {
        throw new InvalidArgumentException('Your form expired. Review it and submit again.');
    }
}

function viewer(): ?array
{
    session();
    if (!isset($_SESSION['uid'])) {
        return null;
    }
    return query('SELECT id, email, role FROM users WHERE id = ? AND active = 1', [$_SESSION['uid']])->fetch() ?: null;
}

function throttle(string $scope, int $limit, int $seconds): void
{
    $window = intdiv(time(), $seconds);
    $bucket = hash('sha256', $scope . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local'));
    $count = (int) query(
        'INSERT INTO rate_limits (bucket, window, hits, expires) VALUES (?, ?, 1, ?)
         ON CONFLICT(bucket, window) DO UPDATE SET hits = hits + 1 RETURNING hits',
        [$bucket, $window, ($window + 2) * $seconds]
    )->fetchColumn();
    if ($count > $limit) {
        http_response_code(429);
        throw new InvalidArgumentException('Too many attempts. Try again later.');
    }
    if ($count === 1) {
        query('DELETE FROM rate_limits WHERE expires < ?', [time()]);
    }
}

function email(string $address): string
{
    $address = strtolower($address);
    if (strlen($address) > 254 || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }
    return $address;
}

function passwordHash(string $password): string
{
    if (strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
        throw new InvalidArgumentException('Use a password between 12 and 72 bytes.');
    }
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function startLogin(int $id): void
{
    session();
    session_regenerate_id(true);
    $_SESSION = ['uid' => $id, 'touched' => time()];
}

function flash(string $message): void
{
    session();
    $_SESSION['flash'] = $message;
}

function takeFlash(): string
{
    session();
    $message = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $message;
}
