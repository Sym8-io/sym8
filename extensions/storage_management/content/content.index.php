<?php
/*
 * Copyright: Deux Huit Huit 2013
 * Copyright: Sym8 since 2025
 * License: MIT, http://deuxhuithuit.mit-license.org
 */

if (!defined("__IN_SYMPHONY__")) {
    die("<h2>Error</h2><p>You cannot directly access this file</p>");
}

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/storage_management/lib/class.cachemanagement.php');

class contentExtensionStorage_managementIndex extends AdministrationPage
{
    private $_Result = null;
    private $showResult = false;

    public function __construct()
    {
        parent::__construct();
        $this->_Result = new XMLElement('span', null, array('class' => 'frame'));
    }

    /**
     * Builds the content view
     */
    public function __viewIndex()
    {
        if (is_callable(array('Symphony', 'Author'))) {
            $author = Symphony::Author();
        } else {
            $author = Administration::instance()->Author;
        }

        if ($author->isAuthor() === true) {
            Administration::instance()->errorPageForbidden();
        }

        $title = __('Storage Management');

        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $title)));

        $this->appendSubheading(__($title));

        $fieldset = new XMLElement('fieldset', null, array('class' => 'settings'));
        $fieldset->appendChild(new XMLElement('legend', __('Disk quota')));

        // The disk quota graph and list already defined in renderDiskQuotaMeter() and
        // renderDiskQuotaList() for the dashboard panel. Reused to avoid double code.
        $fieldset->appendChild(extension_storage_management::renderDiskQuotaMeter());
        $fieldset->appendChild(extension_storage_management::renderDiskQuotaList());

        $this->Form->appendChild($fieldset);

        $fieldset = new XMLElement('fieldset', null, array('class' => 'settings'));
        $fieldset->appendChild(new XMLElement('legend',__('Please choose what to delete')));

        $div = new XMLElement('div', null, array('class' => 'two columns'));

        $div1 = new XMLElement('div', null, array('class' => 'column'));
        $div1->appendChild(new XMLElement('p', __('Database cleaning'), array('class' => 'help')));
        $ul = new XMLElement('ul');
        $ul->appendChild(new XMLElement('li', Widget::Input('action[pur-db]', __('Remove expired DB cache'), 'submit', array('class' => 'outline secondary'))));
        $ul->appendChild(new XMLElement('li', Widget::Input('action[del-db]', __('Clear all DB cache'), 'submit', array('class' => 'outline secondary'))));
        $div1->appendChild($ul);
        $div->appendChild($div1);

        $div2 = new XMLElement('div', null, array('class' => 'column'));
        $div2->appendChild(new XMLElement('p', __('File cleaning'), array('class' => 'help')));
        $ul = new XMLElement('ul');
        $ul->appendChild(new XMLElement('li', Widget::Input('action[del-cachelite]', __('Clear xCacheLite files only'), 'submit', array('class' => 'outline secondary'))));
        $ul->appendChild(new XMLElement('li', Widget::Input('action[del-file]', __('Clear all cached files'), 'submit', array('class' => 'outline secondary'))));
        $div2->appendChild($ul);
        $div->appendChild($div2);

        if (is_dir(CACHE . '/cacheabledatasource')) {
            $fieldset->appendChild(Widget::Input('action[del-cacheabledatasource]', __('Clear Cacheable Datasource files'), 'submit'));
        }

        $fieldset->appendChild($div);

        // Show the result below the action buttons
        $div = new XMLElement('div', null, array('aria-live' => 'polite', 'role' => 'status'));
        if ($this->showResult) {
            $div->appendChild($this->_Result);
        }
        $fieldset->appendChild($div);

        $this->Form->appendChild($fieldset);

    }

    /**
     * Method that handles user actions on the page
     */
    public function __actionIndex()
    {
        // if actions were launch
        if (isset($_POST['action']) && is_array($_POST['action'])) {

            // for each action
            foreach ($_POST['action'] as $key => $action) {
                switch ($key) {
                    case 'del-file':
                        $this->deleteFileCache();
                        break;
                    case 'del-cachelite':
                        $this->deleteCachelite();
                        break;
                    case 'pur-file':
                        $this->purgeFileCache();
                        break;
                    case 'del-db':
                        $this->deleteDBCache();
                        break;
                    case 'pur-db':
                        $this->purgeDBCache();
                        break;
                    case 'del-cacheabledatasource':
                        $this->deleteCDCache();
                        break;
                }
            }
        }
    }

    /* File cache */
    private function deleteFileCache()
    {
        $count = CacheManagement::deleteFileCache();

        $this->_Result->appendChild(new XMLElement('p', __('All %d files in cache deleted.', array($count))));
        if ($count > 0) {
            $this->_Result->appendChild(new XMLElement('p', __('Note: Disk usage values are cached and may take a few minutes to update.')));
        }
        $this->showResult = true;
    }

    /* Cachelite */
    private function deleteCachelite()
    {
        $count = CacheManagement::purgeFileCache(false, '/^cache_(.+)/');

        $this->_Result->appendChild(new XMLElement('p', __('All %d xCacheLite files deleted.', array($count))));
        if ($count > 0) {
            $this->_Result->appendChild(new XMLElement('p', __('Note: Disk usage values are cached and may take a few minutes to update.')));
        }
        $this->showResult = true;
    }

    private function purgeFileCache()
    {
        $count = CacheManagement::purgeFileCache();

        $this->_Result->appendChild(new XMLElement('p', __('Deleted %d expired files.', array($count))));
        $this->showResult = true;
    }

    /* DB cache */
    private function deleteDBCache()
    {
        $count = CacheManagement::deleteDBCache();

        $this->_Result->appendChild(new XMLElement('p', __('All %d entries in cache deleted.', array($count))));
        $this->showResult = true;
    }

    private function purgeDBCache()
    {
        $count = CacheManagement::purgeDBCache();

        $this->_Result->appendChild(new XMLElement('p', __('Deleted %d expired cache entries.', array($count))));
        $this->showResult = true;
    }

    /* Cacheable Datasource */
    private function deleteCDCache()
    {
        $count = CacheManagement::purgeFileCache(false, null, '/cacheabledatasource');

        $this->_Result->appendChild(new XMLElement('p', __('All %d Cacheable Datasource files deleted.', array($count))));
        $this->showResult = true;
    }

}
