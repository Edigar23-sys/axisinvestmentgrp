<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__ . '/../app/bootstrap.php';

try {
    $address = email($argv[1] ?? '');
    if (($argv[2] ?? '') === '--invite') {
        $db = db();
        $db->beginTransaction();
        try {
            query("INSERT INTO users (email,role) VALUES (?,'admin') ON CONFLICT(email) DO NOTHING", [$address]);
            $userId = query(
                "SELECT id FROM users WHERE email = ? AND role = 'admin' AND password_hash IS NULL AND active = 1",
                [$address]
            )->fetchColumn();
            if (!$userId) {
                throw new InvalidArgumentException('This address cannot receive a first-time administrator invitation.');
            }
            $token = bin2hex(random_bytes(32));
            query(
                'INSERT INTO invites (token_hash,user_id,expires) VALUES (?,?,?)
                 ON CONFLICT(user_id) DO UPDATE SET token_hash = excluded.token_hash, expires = excluded.expires',
                [hash('sha256', $token), $userId, time() + 172800]
            );
            $db->commit();
        } catch (Throwable $error) {
            $db->rollBack();
            throw $error;
        }
        echo rtrim($config['origin'], '/') . '/admin/activate?token=' . $token . PHP_EOL;
        exit;
    }
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
