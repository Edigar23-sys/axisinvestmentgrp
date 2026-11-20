<?php
declare(strict_types=1);

const COLLECTIONS = [
    'projects' => ['Projects', ['Technology & AI', 'Financial & Capital Markets', 'Media, Entertainment & Advertising', 'Publishing', 'Mining', 'Food Security', 'Energy']],
    'products' => ['Investment products', ['Investment', 'Capital mobilization', 'SACCOs']],
    'portfolio' => ['Portfolio brands', []],
    'insights' => ['Insights & resources', ['Article', 'Blog', 'White paper', 'Resource', 'Media']],
];
const REQUEST_STATUSES = ['Received', 'In review', 'Approved', 'Closed'];

function required(string $key, string $label, int $max, bool $mandatory = true): string
{
    $value = field($key);
    if (($mandatory && $value === '') || strlen($value) > $max || !preg_match('//u', $value)) {
        throw new InvalidArgumentException("Check the $label field. The maximum length is $max bytes.");
    }
    return $value;
}

function passwordInput(): string
{
    return is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
}

function login(string $role): void
{
    throttle('login', 12, 900);
    $address = email(field('email'));
    $password = passwordInput();
    $user = query('SELECT * FROM users WHERE email = ? AND role = ? AND active = 1', [$address, $role])->fetch();
    $valid = strlen($password) <= 72 && password_verify($password, $user['password_hash'] ?? DUMMY_HASH);
    if (!$valid || empty($user['password_hash'])) {
        throw new InvalidArgumentException('The email or password was incorrect.');
    }
    startLogin((int) $user['id']);
    redirect($role === 'admin' ? '/admin' : '/investors');
}

function submitRequest(string $kind): void
{
    throttle('enquiry', 12, 3600);
    $user = viewer();
    $investor = ($user['role'] ?? '') === 'investor' ? $user : null;
    if (field('consent') !== 'confirmed') {
        throw new InvalidArgumentException('Confirm that Axis may contact you about your request.');
    }
    query(
        'INSERT INTO requests (user_id,kind,name,email,organization,country,topic,message) VALUES (?,?,?,?,?,?,?,?)',
        [
            $investor['id'] ?? null, $kind, required('name', 'name', 120),
            $investor['email'] ?? email(field('email')),
            required('organization', 'organization', 160, in_array($kind, ['Institutional', 'SACCO'], true)),
            required('country', 'country', 100),
            required('topic', 'area of interest', 180, false),
            required('message', 'message', 5000),
        ]
    );
    flash('Your request was received. The Axis team can follow up using the email address you provided.');
    redirect($kind === 'Advisory' ? '/advisory#enquire' : '/investors');
}

function requestRecord(int $id): array
{
    return query('SELECT * FROM requests WHERE id = ?', [$id])->fetch() ?: throw new InvalidArgumentException('Request unavailable.');
}

function issueInvite(int $requestId): void
{
    global $config;
    $db = db();
    $db->beginTransaction();
    try {
        $request = requestRecord($requestId);
        if ($request['kind'] === 'Advisory' || $request['status'] !== 'Approved') {
            throw new InvalidArgumentException('Approve an investor request before creating an invitation.');
        }
        query("INSERT INTO users(email,role) VALUES (?,'investor') ON CONFLICT(email) DO NOTHING", [$request['email']]);
        $user = query("SELECT id, password_hash FROM users WHERE email = ? AND role = 'investor' AND active = 1", [$request['email']])->fetch();
        if (!$user || $user['password_hash']) {
            throw new InvalidArgumentException('This address already has an account or is unavailable. An existing investor can submit a request from their portal.');
        }
        $token = bin2hex(random_bytes(32));
        query(
            'INSERT INTO invites (token_hash,user_id,expires) VALUES (?,?,?)
             ON CONFLICT(user_id) DO UPDATE SET token_hash = excluded.token_hash, expires = excluded.expires',
            [hash('sha256', $token), $user['id'], time() + 172800]
        );
        query('UPDATE requests SET user_id = ? WHERE id = ?', [$user['id'], $requestId]);
        $db->commit();
    } catch (Throwable $error) {
        $db->rollBack();
        throw $error;
    }
    $_SESSION['invite_links'][$requestId] = rtrim($config['origin'], '/') . '/investors/activate?token=' . $token;
    redirect('/admin/requests?id=' . $requestId);
}

function activateAccount(string $token, string $role): void
{
    throttle('activate', 12, 900);
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
        throw new InvalidArgumentException('This invitation is invalid or has expired. Contact Axis for a new link.');
    }
    $password = passwordHash(passwordInput());
    $db = db();
    $db->beginTransaction();
    try {
        $hash = hash('sha256', $token);
        $updated = query(
            'UPDATE users SET password_hash = ? WHERE role = ? AND active = 1 AND password_hash IS NULL
             AND id = (SELECT user_id FROM invites WHERE token_hash = ? AND expires >= ?)',
            [$password, $role, $hash, time()]
        );
        if (!$updated->rowCount()) {
            throw new InvalidArgumentException('This invitation is invalid or has expired. Contact Axis for a new link.');
        }
        $userId = (int) query('SELECT user_id FROM invites WHERE token_hash = ?', [$hash])->fetchColumn();
        query('DELETE FROM invites WHERE token_hash = ?', [$hash]);
        $db->commit();
    } catch (Throwable $error) {
        $db->rollBack();
        throw $error;
    }
    startLogin($userId);
    redirect($role === 'admin' ? '/admin' : '/investors');
}

function published(string $kind, string $category = '', int $limit = 24, int $offset = 0): array
{
    return query(
        'SELECT id,slug,title,category,summary,image_id,external_url,body,first_published_at FROM content
         WHERE kind = ? AND published = 1 AND (? = \'\' OR category = ?)
         ORDER BY first_published_at DESC, id DESC LIMIT ? OFFSET ?',
        [$kind, $category, $category, $limit, $offset]
    )->fetchAll();
}

function upload(string $key, ?int $previous): ?int
{
    $file = $_FILES[$key] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return field('remove_' . $key) === 'yes' ? null : $previous;
    }
    $images = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $files = ['application/pdf', 'video/mp4', 'video/webm', 'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/x-wav'];
    $limit = $key === 'image' ? 6 : 20;
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size'] > $limit * 1024 * 1024) {
        throw new InvalidArgumentException("Upload a file no larger than $limit MB.");
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, $key === 'image' ? $images : $files, true)) {
        throw new InvalidArgumentException($key === 'image' ? 'Use a JPEG, PNG, WebP or GIF image.' : 'Use PDF, MP4, WebM, MP3, Ogg or WAV.');
    }
    if ($key === 'image') {
        $size = @getimagesize($file['tmp_name']);
        if (!$size || $size[0] * $size[1] > 24000000) {
            throw new InvalidArgumentException('Use an image up to 24 megapixels.');
        }
    }
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', basename($file['name']));
    $statement = db()->prepare('INSERT INTO assets (mime,name,data) VALUES (?,?,?)');
    $statement->bindValue(1, $mime);
    $statement->bindValue(2, substr($filename, 0, 180) ?: 'download');
    $statement->bindValue(3, file_get_contents($file['tmp_name']), PDO::PARAM_LOB);
    $statement->execute();
    return (int) db()->lastInsertId();
}

function saveContent(string $kind, int $id): void
{
    $title = required('title', 'title', 150);
    $slug = strtolower(required('slug', 'address', 95, false));
    $slug = $slug ?: trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) || strlen($slug) > 95) {
        throw new InvalidArgumentException('Enter a page address using lowercase letters, numbers and hyphens.');
    }
    $category = required('category', 'category', 120, $kind !== 'portfolio');
    if ($kind !== 'portfolio' && !in_array($category, COLLECTIONS[$kind][1], true)) {
        throw new InvalidArgumentException('Choose a category from the list.');
    }
    $url = required('external_url', 'website address', 500, false);
    if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https')) {
        throw new InvalidArgumentException('Enter a full HTTPS website address.');
    }
    $summary = required('summary', 'summary', 450);
    $body = required('body', 'page content', 24000, false);
    $published = field('visibility') === 'published' ? 1 : 0;
    $db = db();
    $db->beginTransaction();
    try {
        $old = $id ? query('SELECT * FROM content WHERE id = ? AND kind = ?', [$id, $kind])->fetch() : [];
        if ($id && !$old) {
            throw new InvalidArgumentException('This item is unavailable.');
        }
        if (query('SELECT 1 FROM content WHERE kind = ? AND slug = ? AND id <> ?', [$kind, $slug, $id])->fetchColumn()) {
            throw new InvalidArgumentException('That page address is already in use.');
        }
        $image = upload('image', $old['image_id'] ?? null);
        $attachment = $kind === 'portfolio' ? null : upload('attachment', $old['file_id'] ?? null);
        if ($published && $kind === 'products' && !$image) {
            throw new InvalidArgumentException('Add a cover image before publishing this product.');
        }
        if ($published && $kind === 'portfolio' && (!$image || !$url)) {
            throw new InvalidArgumentException('Add a logo and website address before publishing this brand.');
        }
        $date = $old['first_published_at'] ?? ($published ? gmdate('Y-m-d H:i:s') : null);
        $values = [$slug, $title, $category, $summary, $body, $url, $image, $attachment, $published, $date];
        if ($id) {
            query('UPDATE content SET slug=?,title=?,category=?,summary=?,body=?,external_url=?,image_id=?,file_id=?,published=?,first_published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [...$values, $id]);
        } else {
            query('INSERT INTO content (slug,title,category,summary,body,external_url,image_id,file_id,published,first_published_at,kind) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [...$values, $kind]);
            $id = (int) $db->lastInsertId();
        }
        foreach (array_filter([$old['image_id'] ?? null, $old['file_id'] ?? null]) as $assetId) {
            query('DELETE FROM assets WHERE id = ? AND NOT EXISTS (SELECT 1 FROM content WHERE image_id = ? OR file_id = ?)', [$assetId, $assetId, $assetId]);
        }
        $db->commit();
    } catch (Throwable $error) {
        $db->rollBack();
        throw $error;
    }
    flash($published ? 'Published.' : 'Draft saved.');
    redirect('/admin/' . $kind . '?id=' . $id);
}

function asset(int $id): never
{
    $item = query(
        'SELECT a.* FROM assets a JOIN content c ON a.id IN (c.image_id,c.file_id) WHERE a.id = ? AND c.published = 1',
        [$id]
    )->fetch();
    if (!$item && (viewer()['role'] ?? '') === 'admin') {
        $item = query('SELECT * FROM assets WHERE id = ?', [$id])->fetch();
    }
    if (!$item) {
        http_response_code(404);
        exit;
    }
    $data = $item['data'];
    $size = strlen($data);
    $disposition = $item['mime'] === 'application/pdf' ? 'attachment' : 'inline';
    header('Content-Type: ' . $item['mime']);
    header('Content-Disposition: ' . $disposition . '; filename="' . $item['name'] . '"');
    header('Cache-Control: no-store');
    header('Accept-Ranges: bytes');
    $range = $_SERVER['HTTP_RANGE'] ?? '';
    if ($range && preg_match('/^bytes=(\d+)-(\d*)$/D', $range, $matches)) {
        $start = (int) $matches[1];
        $end = $matches[2] === '' ? $size - 1 : min((int) $matches[2], $size - 1);
        if ($start > $end) {
            header('Content-Range: bytes */' . $size, true, 416);
            exit;
        }
        header("Content-Range: bytes $start-$end/$size", true, 206);
        $data = substr($data, $start, $end - $start + 1);
    }
    header('Content-Length: ' . strlen($data));
    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
        echo $data;
    }
    exit;
}
