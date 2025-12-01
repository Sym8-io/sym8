<?php

// DO NOT ALTER THIS FILE!
// Instead, create /manifest/validators.php and add/edit pairs there.

$validators = array(
    // The number field is now available as a native core field
    'number' => '/^-?(?:\d+(?:\.\d+)?|\.\d+)$/i',
    // The email field is now available as a native core field but needed for new authors :-/
    'email' => '/^\w(?:\.?[\w%+-]+)*@\w(?:[\w-]*\.)+?[a-z]{2,}$/i',
    // The url field is now available as a native core field
    'URI' => '/^[^\s:\/?#]+:(?:\/{2,3})?[^\s.\/?#]+(?:\.[^\s.\/?#]+)*(?:\/?[^\s?#]*\??[^\s?#]*(#[^\s#]*)?)?$/',
);

$upload = array(
    // Suggested order: Most common file types first
    // Webp images are now availabel as image format
    'image' => '/\.(?:bmp|gif|jpe?g|png|webp|avif)$/i',
    'document' => '/\.(?:docx?|pdf|rtf|txt)$/i',
    'video' => '/\.(?:mp4|m4v|webm|ogv)$/i',
    'archive' => '/\.(?:zip)$/i',
);

if (file_exists(MANIFEST . '/validators.php') && is_readable(MANIFEST . '/validators.php')) {
    include MANIFEST . '/validators.php';
}
