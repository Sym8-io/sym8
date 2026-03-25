<?php

class migration_2842 extends Migration
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
        return '2.84.2';
    }

    public static function getReleaseNotes()
    {
        return 'https://sym8.io/releases/2.84.2/';
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
            $notes[] = __("With this update, two new extensions are introduced:");
            $notes[] = __("<strong>Storage Management</strong> gives you an overview of your website storage usage (vHost), broken down by specific directories, and lets you clean various caches (expired or all cache files) directly from the backend.");
            $notes[] = __("<strong>xCacheLite</strong> is a lightweight full-page caching extension for Sym8, designed to provide predictable and safe cache behavior without relying on browser-side caching.");
            $notes[] = __("The installer has been completely redesigned and now uses Pico CSS, offering full RTL support.");
        }

        return $notes;
    }

    public static function postUpdateNotes()
    {
        $notes = array();

        $notes[] = __("The installer is only visible during the initial setup.<br />To see the redesigned version, set up a new Sym8 project.");

        return $notes;
    }
}
