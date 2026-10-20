<?php
/**
 * DevTech Ventures - site configuration.
 *
 * To change settings on a live server without editing this file, create
 * includes/config.local.php and define() any of the constants below there.
 */
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// ---- Business details -------------------------------------------------------
defined('SITE_NAME')        || define('SITE_NAME', 'DevTech Ventures');
defined('SITE_TAGLINE')     || define('SITE_TAGLINE', 'Everything You Need. All in One Place.');
defined('PHONE_INTL')       || define('PHONE_INTL', '+254718249497');      // used for tel: links
defined('PHONE_DISPLAY')    || define('PHONE_DISPLAY', '+254 718 249 497'); // shown to visitors
defined('WHATSAPP_NUMBER')  || define('WHATSAPP_NUMBER', '254718249497');  // digits only, for wa.me

// ---- Database ---------------------------------------------------------------
defined('DB_HOST') || define('DB_HOST', '127.0.0.1');
defined('DB_NAME') || define('DB_NAME', 'devtech_ventures');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');

// ---- Uploads ----------------------------------------------------------------
defined('UPLOAD_DIR')      || define('UPLOAD_DIR', dirname(__DIR__) . '/assets/images/products/');
defined('UPLOAD_URL')      || define('UPLOAD_URL', 'assets/images/products/');
defined('MAX_UPLOAD_BYTES') || define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);

// ---- Currency ---------------------------------------------------------------
defined('CURRENCY') || define('CURRENCY', 'KSh');

date_default_timezone_set('Africa/Nairobi');
