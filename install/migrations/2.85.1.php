<?php

class migration_2851 extends Migration
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
        return '2.85.1';
    }

    public static function getReleaseNotes()
    {
        return 'https://sym8.io/releases/2-85-1/';
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
            $notes[] = __("Major accessibility, UI, and security improvements");
            $notes[] = __("Installer and authentication interfaces have been modernized with accessible form markup using explicit label/input associations, improved error descriptions, and better support for assistive technologies.");
            $notes[] = __("Login, password forgotten, unban-by-email interfaces, and all Symphony error templates have been redesigned using Pico CSS, providing a cleaner, responsive, and mobile-friendly user experience.");
            $notes[] = __("The Anti-Brute-Force extension and Symphony core now prevent blocked authentication requests from being processed again by discarding submitted POST data. This avoids unnecessary escalation from temporary bans to blacklisting while keeping the existing protection mechanisms intact.");
        }

        return $notes;
    }

    public static function postUpdateNotes()
    {
        $notes = array();

        $notes[] = __("Please update the following extension via the “Extensions” page: “Anti Brute Force“.");

        return $notes;
    }
}
