<?php

class migration_2830 extends Migration
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
        return '2.83.0';
    }

    public static function getReleaseNotes()
    {
        return 'https://sym8.io/releases/2.83.0/';
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
        }
    }

    public static function preUpdateNotes()
    {
        $notes = array();

        return $notes;
    }

    public static function postUpdateNotes()
    {
        $notes = array();

        return $notes;
    }
}
