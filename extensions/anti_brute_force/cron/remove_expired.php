<?php
/**
 * The cron script is CLI-only and must be executed via a server cron job.
 * HTTP-based cron services are intentionally not supported for security reasons.
 */
if (php_sapi_name() !== 'cli') {
    exit;
}

define('DOCROOT', str_replace('/extensions/anti_brute_force/cron', '', rtrim(dirname(__FILE__), '\\/') ));

if (file_exists(DOCROOT . '/vendor/autoload.php')) {
    require_once(DOCROOT . '/vendor/autoload.php');
    require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
} else {
    echo gmdate('r') . " [ABF] Failed to load symphony.\n";
    exit;
}

// creates the DB
Administration::instance();

require_once(DOCROOT . '/extensions/anti_brute_force/extension.driver.php');

if (!ABF::instance()->removeExpiredEntries()) {
    echo gmdate('r') . " [ABF] Failed to delete expired entries.\n";
    exit;
} else {
    echo gmdate('r') . " [ABF] cleanup completed.\n";
    exit;
}
