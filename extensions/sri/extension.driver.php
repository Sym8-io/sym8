<?php
/**
 * Copyright: Deux Huit Huit 2016
 * LICENCE: MIT https://deuxhuithuit.mit-license.org;
 */

if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

/**
 *
 * @author Deux Huit Huit
 * https://deuxhuithuit.com/
 *
 */
class extension_sri extends Extension
{

    /**
     * Name of the extension
     * @var string
     */
    const EXT_NAME = 'Subresource Integrity';

    /* ********* INSTALL/UPDATE/UNINSTALL ******* */

    /**
     * Creates the table needed for the settings of the field
     */
    public function install()
    {
        return true;
    }

    /**
     * This method will update the extension according to the
     * previous and current version parameters.
     * @param string $previousVersion
     */
    public function update($previousVersion = false)
    {
        $ret = true;

        if (!$previousVersion) {
            $previousVersion = '0.0.1';
        }

        // less than 0.0.1
        if ($ret && version_compare($previousVersion, '0.0.1', '<')) {

        }

        return $ret;
    }

    /**
     * Drops the table needed for the settings of the field
     */
    public function uninstall()
    {
        return true;
    }

}
