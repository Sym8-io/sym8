<?php

class migration_2850 extends Migration
{
    private static $current;

    private static function getCurrentVersion()
    {
        if (!self::$current) {
            self::$current = Symphony::Configuration()->get('version', 'symphony');
        }
        return self::$current;
    }

    public static function getVersion()
    {
        return '2.85.0';
    }

    public static function getReleaseNotes()
    {
        return 'https://sym8.io/releases/2-85-0/';
    }

    public static function upgrade()
    {
        // Version check first
        // to prevent upgrading old Symphony instances
        if (version_compare(self::getCurrentVersion(), '2.83.0', '<')) {
            Symphony::Log()->pushToLog(
                __("Upgrade to %s skipped: Symphony version %s too old. Manual migration required.",
                   array(
                       self::getVersion(),
                       self::getCurrentVersion()
                   )
                ),
                E_NOTICE, true
            );
            return false;
        } else {
            // Upgrades for extensions and SQL here
            Symphony::Log()->pushToLog("Running migration " . self::getVersion(), E_NOTICE, true);

            $copyNumberField = General::copyFile(DOCROOT . '/install/installable/field.number.php', SYMPHONY . '/lib/toolkit/fields/field.number.php');
            if ($copyNumberField === false) {
                Symphony::Log()->pushToLog("The file `" . DOCROOT . "/install/installable/field.number.php` could not be copied to `" . DOCROOT . "/symphony/lib/toolkit/fields/`. Please copy the file manually.", E_NOTICE, true);
            } else {
                Symphony::Log()->pushToLog("The file `field.number.php` was successfully copied to `" . SYMPHONY . "/lib/toolkit/fields/`", E_NOTICE, true);
            }

            // Update the version information
            return parent::upgrade();
        }
    }

    public static function preUpdateNotes()
    {
        $notes = array();

        if (version_compare(self::getCurrentVersion(), '2.83.0', '<')) {
            $notes[] = __("🔴 Your current Symphony 2.7.x installation (%s) is too old for an automatic upgrade.
                              Please update manually to at least <code>2.84.1</code> (recommended) first.
                              You can find a documentation for a manual update at %s.",
                          array(
                              "<code>" . self::getCurrentVersion() . "</code>",
                              "<a href=\"https://sym8.io/docs/install/#how-to-upgrade-manually\" target=\"_blank\" rel=\"noopener\">Sym8.io</a>"
                            )
                          );
        } else {
            $notes[] = __("This release focuses on PHP 8.5 compatibility. While the version number suggests a minor update, it includes important internal changes such as the removal of legacy resource handling.");
            $notes[] = __("A new extension is introduced:");
            $notes[] = __("<strong>Subresource Integrity</strong>: Computes base64-encoded SHA hashes (256, 384, 512) for use in <code>link</code> and <code>script</code> elements.");
            $notes[] = __("The following extensions have been updated to newer versions: “Dashboard“, “Order Entries“ and “xCacheLite“.");
            $notes[] = __("The default front-end form markup has been updated to improve accessibility and standards compliance.
            If you use custom templates, please review the release notes, as this may require adjustments to your markup, CSS or JavaScript.");
        }

        return $notes;
    }

    public static function postUpdateNotes()
    {
        $notes = array();

        $notes[] = __("Please update the following extensions via the “Extensions” page: “Dashboard“, “Order Entries“ and “xCacheLite“.");

        return $notes;
    }
}
