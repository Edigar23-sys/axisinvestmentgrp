<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require ROOT . '/app/actions.php';
require ROOT . '/app/public.php';
require ROOT . '/app/portal.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; img-src 'self'; style-src 'self'; script-src 'self'; media-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'none'; object-src 'none'");
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

function handlePost(string $area, string $page, string $kind, ?array $user): void
{
    checkCsrf();
    $action = field('action');
    if ($area === 'advisory' && $page === '') {
        submitRequest('Advisory');
    }
    if ($area === 'investors' && $page === 'apply') {
        submitRequest($kind);
    }
    if (in_array($area, ['admin', 'investors'], true) && $page === 'activate') {
        activateAccount(param('token'), $area === 'admin' ? 'admin' : 'investor');
    }
    if (in_array($area, ['admin', 'investors'], true) && $action === 'login') {
        login($area === 'admin' ? 'admin' : 'investor');
    }
    if ($action === 'logout' && $user && in_array($area, ['admin', 'investors'], true)) {
        $_SESSION = [];
        session_regenerate_id(true);
        redirect('/' . $area);
    }
    if ($area === 'admin' && ($user['role'] ?? '') === 'admin') {
        $id = max(0, (int) param('id'));
        if ($action === 'save' && isset(COLLECTIONS[$page])) {
            saveContent($page, $id);
        }
        if ($page === 'requests' && $action === 'invite') {
            issueInvite($id);
        }
        if ($page === 'requests' && $action === 'status' && in_array(field('status'), REQUEST_STATUSES, true)) {
            requestRecord($id);
            query('UPDATE requests SET status = ? WHERE id = ?', [field('status'), $id]);
            flash('Enquiry status updated.');
            redirect('/admin/requests?id=' . $id);
        }
    }
    throw new InvalidArgumentException('This action is unavailable.');
}

function notFound(): void
{
    http_response_code(404);
    layout('Page not found', function () {
        pageHead('Page not found', 'This page may have moved or is currently unavailable.');
        echo '<section class="section shell">';
        button('/', 'Back to Axis');
        echo '</section>';
    }, private: true);
}

function run(): void
{
    $path = rtrim(explode('?', $_SERVER['REQUEST_URI'], 2)[0], '/');
    if (!preg_match('~^/(?:[a-z0-9-]+)(?:/[a-z0-9-]+)?$~D', $path) && $path !== '') {
        notFound();
        return;
    }
    [$area, $page] = array_pad(explode('/', ltrim($path, '/')), 2, '');
    $public = ['' => 'home', 'about' => 'about', 'sectors' => 'sectors', 'privacy' => 'privacy'];
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD', 'POST'], true)) {
        header('Allow: GET, HEAD, POST');
        http_response_code(405);
        return;
    }
    $isForm = ($area === 'advisory' && $page === '')
        || ($area === 'investors' && in_array($page, ['', 'apply', 'activate'], true))
        || ($area === 'admin' && (in_array($page, ['', 'requests', 'activate'], true) || isset(COLLECTIONS[$page])));
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isForm) {
        header('Allow: GET, HEAD');
        http_response_code(405);
        return;
    }
    $user = $isForm && $area !== 'advisory' && $page !== 'activate' ? viewer() : null;
    $kind = ['institutional' => 'Institutional', 'sacco' => 'SACCO'][param('type')] ?? 'Individual';
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                http_response_code(413);
                throw new InvalidArgumentException('The submitted files exceed the server’s upload limit.');
            }
            handlePost($area, $page, $kind, $user);
        } catch (InvalidArgumentException $invalid) {
            if (http_response_code() < 400) {
                http_response_code(422);
            }
            $error = $invalid->getMessage();
        }
    }
    if ($page === '' && isset($public[$area])) {
        $public[$area]();
    } elseif ($area === 'advisory' && $page === '') {
        advisory($error);
    } elseif ($area === 'products' && $page === 'sacco') {
        $custom = detail('products', 'sacco');
        if (!$custom) {
            sacco();
        }
    } elseif (isset(COLLECTIONS[$area]) && $page === '') {
        listing($area);
    } elseif ($area === 'files' && ctype_digit($page)) {
        asset((int) $page);
    } elseif ($area === 'investors' && $isForm) {
        match ($page) {
            'apply' => applyPage($kind, $error),
            'activate' => activationPage(param('token'), $error, 'investor'),
            default => investorPage(($user['role'] ?? '') === 'investor' ? $user : null, $error),
        };
    } elseif ($area === 'admin' && $isForm) {
        if ($page === 'activate') {
            activationPage(param('token'), $error, 'admin');
        } else {
            adminPage($user, $page, $error);
        }
    } elseif (!isset(COLLECTIONS[$area]) || $area === 'portfolio' || !detail($area, $page)) {
        notFound();
    }
}

try {
    run();
} catch (Throwable $error) {
    error_log($error->getMessage());
    http_response_code(503);
    header('Cache-Control: no-store');
    layout('Temporarily unavailable', function () {
        pageHead('Temporarily unavailable', 'Please try again later or contact info@axisinvestmentgrp.com.');
    }, private: true);
}
