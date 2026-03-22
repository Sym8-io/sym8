<?php
/**
 * Copyright: Deux Huit Huit 2013-2014
 * Copyright: Sym8 since 2025
 * License: MIT, http://deuxhuithuit.mit-license.org
 */

if (!defined("__IN_SYMPHONY__")) {
    die("<h2>Error</h2><p>You cannot directly access this file</p>");
}

if (!defined('DOCROOT')) {
    define('DOCROOT', str_replace(EXTENSIONS . '/storage_management', '', rtrim(dirname(__FILE__), '\\/') ));
}

class extension_storage_management extends Extension
{
    /**
     * Name of the extension
     * @var string
     */
    const EXT_NAME = 'Storage Management';

    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/backend/',
                'delegate' => 'NavigationPreRender',
                'callback' => 'navigationPreRender'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'AddCustomPreferenceFieldsets',
                'callback' => 'appendPreferences'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'Save',
                'callback' => '__SavePreferences'
            ),
            array(
                'page' => '/backend/',
                'delegate' => 'DashboardPanelRender',
                'callback' => 'render_panel'
            ),
            array(
                'page' => '/backend/',
                'delegate' => 'DashboardPanelTypes',
                'callback' => 'dashboard_panel_types'
            )
        );
    }

    /*-------------------------------------------------------------------------
     *       Extension
     *-------------------------------------------------------------------------*/

    public function install()
    {
        return true;
    }

    public function update($previousVersion = false)
    {
        return true;
    }

    public function uninstall()
    {
        // Remove preferences
        Symphony::Configuration()->remove('storage_management');
        return Symphony::Configuration()->write();
    }

    /*-------------------------------------------------------------------------
     *       Preferences
     *-------------------------------------------------------------------------*/

    /**
     * append the preferences field
     * @return void
     */
    public function appendPreferences($context)
    {
        $group = new XMLElement('fieldset');
        $group->setAttribute('class', 'settings');
        $group->appendChild(new XMLElement('legend', __(self::EXT_NAME)));

        $label = Widget::Label('Total webspace ');
        $help = new XMLElement('span', __('(e.g. "500 MB", "750 MB", "5 GB" or "1.5 TB")'), array('class' => 'help'));
        $label->appendChild($help);
        if (!empty(Symphony::Configuration()->get('total_webspace', 'storage_management'))) {
            $value = General::formatFilesize(Symphony::Configuration()->get('total_webspace', 'storage_management'), true);
        } else {
            $value = 0;
        }
        $input = Widget::Input('settings[storage_management][total_webspace]', strval($value));
        $label->appendChild($input);
        if (isset($context['errors']['total_webspace'])) {
            $group->appendChild(Widget::Error($label, $context['errors']['total_webspace']));
        } else {
            $group->appendChild($label);
        }

        $context['wrapper']->appendChild($group);
    }

    /**
    * Save preferences
    *
    * @param array $context
    * delegate context
    */
    public function __SavePreferences($context)
    {
        $value = trim($context['settings']['storage_management']['total_webspace']);
        if (!empty($value)) {
            if (!preg_match('/^\d+(\.\d+)?\s*(bytes|kb|mb|gb|tb)?$/i', $value)) {
                $context['errors']['total_webspace'] = __('Please enter a valid filesize, e.g. "500 MB", "5 GB" or "1.5 TB".');
            } else {
                // Calculate GB to bytes
                $context['settings']['storage_management']['total_webspace'] = General::convertHumanFileSizeToBytes($value);
            }
        }

    }

    /**
     * Delegate fired to add a link to Cache Management
     */
    public function fetchNavigation()
    {
        if (is_callable(array('Symphony', 'Author'))) {
            $author = Symphony::Author();
        } else {
            $author = Administration::instance()->Author;
        }

        // Work around single group limit in nav
        $group = $author->isDeveloper() ? 'developer' : 'manager';

        return array(
                array (
                    'location' => __('System'),
                    'name' => __(self::EXT_NAME),
                    'link' => '/',
                    'limit' => $group,
                ) // nav group
            ); // nav
    }

    /*-------------------------------------------------------------------------
     *       Dashboard panel
     *-------------------------------------------------------------------------*/

    public function dashboard_panel_types($context)
    {
        $context['types']['disk_quota'] = 'Disk quota';
    }

    public function render_panel($context)
    {
        if ($context['type'] !== 'disk_quota') {
            return;
        }

        $config = $context['config'];

        $context['panel']->appendChild(self::renderDiskQuotaMeter());
        $context['panel']->appendChild(self::renderDiskQuotaList());
    }

    public static function renderDiskQuotaMeter(): XMLElement
    {
        $div = new XMLElement('div', null, array('class' => 'disk-usage__meter'));

        $totalQuota = self::getTotalQuota();

        $totalWebspace = Symphony::Configuration()->get('total_webspace', 'storage_management');
        $meterAttr = array(
                        'id' => 'disk-usage',
                        'min' => '0',
                        'max' => '100',
                        'low' => '75',
                        'high' => '85',
                        'optimum' => '50'
                     );
        if ($totalWebspace === null || $totalWebspace === '0') {
            $meter = new XMLElement('meter', null, $meterAttr);
            $meter->setAttribute('value', '0');
            $meter->setAttribute('aria-describedby', 'disk-usage-hint');
            $div->appendChild($meter);
            $div->appendChild(new XMLElement('p', __('Enter the total webspace size in the Symphony Preferences to see the disk usage graph'), array('id' => 'disk-usage-hint',  'class' => 'help')));
        } else {
            $value = ($totalQuota) * 100 / $totalWebspace;
            $meter = new XMLElement('meter', null, $meterAttr);
            $meter->setAttribute('value', number_format($value, 1));
            $div->appendChild($meter);
        }

        return $div;
    }

    public static function renderDiskQuotaList(): XMLElement
    {
        $totalWebspace = Symphony::Configuration()->get('total_webspace', 'storage_management');
        $totalQuota = self::getTotalQuota();

        // Cacheable directories
        // Longer cache time for more static directories and
        // shorter cache time for dynamic directories (more frequent changes).
        $cacheables = array(
                        '0' => [
                            'path' => '/extensions',
                            'cache_time' => 352
                        ],
                        '1' => [
                            'path' => '/manifest/cache',
                            'cache_time' => 22
                        ],
                        '2' => [
                            'path' => '/symphony',
                            'cache_time' => 372
                        ],
                        '3' => [
                            'path' => '/workspace',
                            'cache_time' => 32
                        ]
                      );

        $ul = new XMLElement('ul', null, array('class' => 'disk-usage__list'));
        if ($totalWebspace === null || $totalWebspace === '0') {
            $ul->appendChild(new XMLElement('li', __('Total: %s in use', array(General::formatFilesize($totalQuota)))));
        } else {
            $ul->appendChild(new XMLElement('li', __('Total: %s of %s', array(General::formatFilesize($totalQuota), General::formatFilesize($totalWebspace, true)))));
        }

        $cache = new Cacheable(Administration::instance()->Database());
        foreach ($cacheables as $cacheable) {
            $cacheTime = $cacheable['cache_time'];
            $path = $cacheable['path'];
            $cacheId = md5($path);
            $data = $cache->check($cacheId);
            if (!$data) {
                $size = self::getDirectorySize(DOCROOT . $path);
                $cache->write($cacheId, $size, $cacheTime);
            } else {
                $size = json_decode($data['data']);
            }
            $ul->appendChild(new XMLElement('li', __("<code>$path</code> %s", array(General::formatFilesize($size)))));
        }

        return $ul;
    }

    public static function getTotalQuota(): int
    {
        $cacheTime = 47;
        $cacheId = md5('storage_management_total_chache');
        $cache = new Cacheable(Administration::instance()->Database());
        $dataTotalQuota = $cache->check($cacheId);
        if (!$dataTotalQuota) {
            $totalQuota = self::getDirectorySize(DOCROOT);
            $cache->write($cacheId, $totalQuota, $cacheTime);
        } else {
            $totalQuota = json_decode($dataTotalQuota['data']);
        }

        return $totalQuota;
    }

    public static function getDirectorySize(string $path): int
    {
        $size = 0;

        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        ) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

}
