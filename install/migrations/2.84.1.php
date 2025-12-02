<?php

class migration_2841 extends Migration
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
        return '2.84.1';
    }

    public static function getReleaseNotes()
    {
        return 'https://sym8.io/releases/2.84.1/';
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
            $notes[] = __("The update process is now controlled exclusively via the backend. 🔐");
            $notes[] = __("This update fixes several issues in Sym8, the extensions “JIT Image Manipulation” and “Dashboard”.");
            $notes[] = __("The installer has been fixed so that when a workspace folder is found, the necessary subfolders are also checked for existence.");
            $notes[] = __("The template files are now copied directly to the workspace subfolders during the installation process.");
            $notes[] = __("🖼️ Sym8 and the extension “JIT Image Manipulation” now support the following image formats: <code>GIF</code>, <code>JPEG</code>, <code>PNG</code>, <code>BMP</code>, <code>WebP</code>, and <code>AVIF</code>.");
        }

        return $notes;
    }

    public static function postUpdateNotes()
    {
        $notes = array();

        $notes[] = __("An update is available for the extensions “Dashboard”, “JIT Image Manipulation”, “Image Preview” and “Unique Upload Field”. Please update them manually on the Extension page (System → Extensions).");
        $notes[] = __("When the JIT extension has been updated, the global image quality is set to <strong>90</strong> (default). However, you can now easily adjust this in the JIT Preferences.");

        return $notes;
    }
}
