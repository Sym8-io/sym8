<?php

class Updater extends Installer
{
    /**
     * This function returns an instance of the Updater
     * class. It is the only way to create a new Updater, as
     * it implements the Singleton interface
     *
     * @return Updater
     */
    public static function instance()
    {
        if (!(self::$_instance instanceof Updater)) {
            self::$_instance = new Updater;
        }

        return self::$_instance;
    }

    /**
     * Initialises the language by looking at the existing
     * configuration
     */
    public static function initialiseLang()
    {
        Lang::set(Symphony::Configuration()->get('lang', 'symphony'), false);
    }

    /**
     * Initialises the configuration object by loading the existing
     * website config file
     */
    public static function initialiseConfiguration(array $data = array())
    {
        parent::initialiseConfiguration();
    }

    /**
     * Overrides the `initialiseLog()` method and writes
     * logs to manifest/logs/update
     */
    public static function initialiseLog($filename = null)
    {
        if (is_dir(INSTALL_LOGS) || General::realiseDirectory(INSTALL_LOGS, self::Configuration()->get('write_mode', 'directory'))) {
            parent::initialiseLog(INSTALL_LOGS . '/update');
        }
    }

    /**
     * Overrides the default `initialiseDatabase()` method
     * This allows us to still use the normal accessor
     */
    public static function initialiseDatabase()
    {
        self::setDatabase();

        $details = Symphony::Configuration()->get('database');

        try {
            Symphony::Database()->connect(
                $details['host'],
                $details['user'],
                $details['password'],
                $details['port'],
                $details['db']
            );
        } catch (DatabaseException $e) {
            self::__abort(
                'There was a problem while trying to establish a connection to the MySQL server. Please check your settings.',
                time()
            );
        }

        // MySQL: Setting prefix & character encoding
        Symphony::Database()->setPrefix($details['tbl_prefix']);
        Symphony::Database()->setCharacterEncoding();
        Symphony::Database()->setCharacterSet();
    }

    public function run()
    {
        /**
         * The updater page is now just a placeholder indicating that
         * Symphony/Sym8 is already installed.
         *
         * The update process itself now takes place in the backend (System → Update).
         * This ensures that no update process is started by website visitors or
         * other unauthorized users trough the public /install/ URL.
         */

        Symphony::Log()->pushToLog(
            sprintf('Updater accessed – backend-only updates enforced.'),
            E_NOTICE, true
        );

        self::__render(new UpdaterPage('uptodate'));
    }
}
