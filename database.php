<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection (prepared statements only, exceptions on error).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
           . '<title>' . htmlspecialchars(SITE_NAME) . '</title>'
           . '<body style="font-family:system-ui,sans-serif;text-align:center;padding:12vh 1rem;color:#1d2a24">'
           . '<h1>We&rsquo;ll be right back</h1>'
           . '<p>Our catalogue is temporarily unavailable. Please call us on '
           . '<a href="tel:' . htmlspecialchars(PHONE_INTL) . '">' . htmlspecialchars(PHONE_DISPLAY) . '</a> '
           . 'or message us on <a href="https://wa.me/' . htmlspecialchars(WHATSAPP_NUMBER) . '">WhatsApp</a>.</p></body>';
        exit;
    }

    return $pdo;
}
