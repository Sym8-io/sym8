<?php
/**
 * The cron script is CLI-only and must be executed via a server cron job.
 * HTTP-based cron services are intentionally not supported for security reasons.
 */
if (php_sapi_name() !== 'cli') {
    exit;
}

define('DOCROOT', str_replace('/extensions/xcachelite/cron', '', rtrim(dirname(__FILE__), '\\/') ));

if (file_exists(DOCROOT . '/vendor/autoload.php')) {
    require_once(DOCROOT . '/vendor/autoload.php');
    require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
} else {
    require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
    require_once(DOCROOT . '/symphony/lib/core/class.cacheable.php');
    require_once(DOCROOT . '/symphony/lib/core/class.symphony.php');
    require_once(DOCROOT . '/symphony/lib/core/class.administration.php');
    require_once(DOCROOT . '/symphony/lib/toolkit/class.general.php');
}

if (Symphony::Configuration()->get('clean-strategy', 'cachelite') === 'cron') {
    echo gmdate('r') . " [xCacheLite] Execution of this cron job granted by Symphony Preferences.\n";

    $cacheDir = DOCROOT . '/manifest/cache';

    $files = glob($cacheDir . '/cache_*');

    // Logging
    $deleted = 0;

    foreach ($files as $file) {
        if (is_file($file) && @unlink($file)) {
            $deleted++;
        }
    }

    echo gmdate('r') . " [xCacheLite] Deleted {$deleted} cache files\n";
} else {
    echo gmdate('r') . " [xCacheLite] Please enable this cron job in the Symphony Preferences.\n";
}
