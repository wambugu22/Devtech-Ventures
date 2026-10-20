<?php
require_once __DIR__ . '/functions.php';

/* ------------------------------------------------------------------ session */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('devtech_admin');
    session_start();
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

/* --------------------------------------------------------------------- CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Invalid or expired form. Please go back, refresh the page and try again.');
    }
}

/* -------------------------------------------------------------------- flash */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $out .= '<div class="alert ' . e($f['type']) . '" role="status">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/* ------------------------------------------------------------------ uploads */

/**
 * Validates and stores one uploaded image. Returns the new file name,
 * null when no file was chosen, and throws RuntimeException on a bad file.
 */
function handle_image_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image could not be uploaded (error code ' . (int) $file['error'] . ').');
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Images must be smaller than ' . (MAX_UPLOAD_BYTES / 1048576) . ' MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($exts[$mime]) || !@getimagesize($file['tmp_name'])) {
        throw new RuntimeException('Only JPG, PNG, WebP or GIF images are allowed.');
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        throw new RuntimeException('The upload folder could not be created.');
    }
    $name = bin2hex(random_bytes(8)) . '.' . $exts[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        throw new RuntimeException('The image could not be saved. Check folder permissions.');
    }
    return $name;
}

/** Turns the awkward $_FILES['x'] multi-upload structure into a list of single files. */
function normalize_files(array $files): array
{
    $out = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $out;
    }
    foreach ($files['name'] as $i => $_) {
        $out[] = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
    }
    return $out;
}

/** Deletes an image file from disk once nothing in the database refers to it. */
function delete_image_file(?string $name): void
{
    if (!$name) {
        return;
    }
    $name = basename($name);
    $st = db()->prepare('SELECT (SELECT COUNT(*) FROM products WHERE image = ?) + (SELECT COUNT(*) FROM product_images WHERE image = ?)');
    $st->execute([$name, $name]);
    if ((int) $st->fetchColumn() === 0 && is_file(UPLOAD_DIR . $name)) {
        @unlink(UPLOAD_DIR . $name);
    }
}
