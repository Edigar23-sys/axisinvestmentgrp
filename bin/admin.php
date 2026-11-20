<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__ . '/../app/bootstrap.php';

try {
    $address = email($argv[1] ?? '');
    $password = bin2hex(random_bytes(16));
    $statement = query(
        "INSERT INTO users (email,password_hash,role) VALUES (?,?,'admin')
         ON CONFLICT(email) DO UPDATE SET password_hash = excluded.password_hash, active = 1 WHERE users.role = 'admin'",
        [$address, passwordHash($password)]
    );
    if (!$statement->rowCount()) {
        throw new InvalidArgumentException('This email is used by an investor account. Use a different administrative email.');
    }
    echo "Administrator: $address\nPassword: $password\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
