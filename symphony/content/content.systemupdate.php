<?php
/**
 * @package content
 */

/**
 * Displays the contents of the Symphony Update page.
 * If an author (Developer) is not logged in,
 * the login form for the backend is displayed.
 */

class contentSystemUpdate extends AdministrationPage
{
    public function build(array $context = array())
    {
        parent::build();
    }

    public function view()
    {
        $installPath = DOCROOT . '/install';
        $installLog =  MANIFEST . '/logs';
        $installURL =  URL . '/install';

        /**
         * Overrides the `initialiseLog()` method and writes
         * logs to manifest/logs/update
         */
        if (is_dir($installLog) || General::realiseDirectory($installLog, self::Configuration()->get('write_mode', 'directory'))) {
            Symphony::initialiseLog($installLog . '/update');
        }

        $currentVersion = Symphony::Configuration()->get('version', 'symphony');

        $this->setPageType('form');
        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Update'), __('Symphony'))));
        $this->addElementToHead(new XMLElement('link', null, array(
            'rel' => 'canonical',
            'href' => SYMPHONY_URL . '/system/update/',
        )));
        $this->appendSubheading(__('Update'));

        if (isset($_POST['action']['update'])) {
#var_dump($_POST);
            if (!XSRF::validateToken($_POST['xsrf'])) {
                throw new Exception(__('Invalid XSRF token.'));
            }

            $notes = array();
            $success = true;

            // Get available migrations
            $migrations = $this->getMigrations();

            $rn = call_user_func(array(end($migrations), 'getReleaseNotes'));

            // Loop over all the available migrations incrementally applying
            // the upgrades. If any upgrade throws an uncaught exception or
            // returns false, this will break and the failure page shown
            foreach ($migrations as $version => $m) {
                $n = call_user_func(array($m, 'postUpdateNotes'));
                if (!empty($n)) {
                    $notes[$version] = $n;
                }

                $success = call_user_func(array($m, 'run'), 'upgrade', Symphony::Configuration()->get('version', 'symphony'));

                Symphony::Log()->pushToLog(
                    sprintf('Updater - Migration to %s was %s', $version, $success ? 'successful' : 'unsuccessful'),
                    E_NOTICE, true
                );

                if (!$success) {
                    $success = false;

                    // Trim postReleaseNotes if failure happens
                    // Cut off anything after the failed version
                    $notes = array_slice($notes, 0, array_search($version, array_keys($notes)), true);

                    break;
                }

            }

            // Create group
            $group = new XMLElement('fieldset');
            $group->setAttribute('class', 'settings');
            $group->appendChild(new XMLElement('legend', __('Update Symphony')));

            $latestVersion = !empty($migrations)
                ? array_keys($migrations)[count($migrations)-1]
                : null;

            $helpVersion = (isset($latestVersion) and $success) ? $latestVersion : $currentVersion;
            $helpLink = '';
            if ($success) {
                $helpLink = '<br /><a href="' . $rn . '" target="_blank" rel="noopener">Release Notes</a>';
            }

            $pVersion = new XMLElement('p', 'Version ' . $helpVersion . $helpLink, array('class' => 'help'));
            $group->appendChild($pVersion);

            #$postNotes = $this->getPostNotes($migrations);
            $postNotes = $notes;
            if (!empty($postNotes)) {
                $div2 = new XMLElement('div');

                $dl = new XMLElement('dl', null, array('class' => 'post-installation-notes'));
                foreach ($postNotes as $version => $notes) {
                    $dl->appendChild(new XMLElement('dt', $version, array('class' => 'help')));
                    $dd = new XMLElement('dd');
                    $ul = new XMLElement('ul');
                    foreach ($notes as $note) {
                        $ul->appendChild(new XMLElement('li', $note));
                    }
                    $dd->appendChild($ul);
                    $dl->appendChild($dd);
                }
                $div2->appendChild($dl);
            }

            if ($success) {
                $div = new XMLElement('div');
                $h3 = new XMLElement('h3', __('Updating Complete'));
                $p1 = new XMLElement('p', __('And the crowd goes wild! A victory dance is in order; and look, your mum is watching. She\'s proud.'));
                $p2 = new XMLElement('p', __('Your mum is also nagging you about %s before you log in.', array(
                                                '<a href="' . URL . '/install/?action=remove">' .
                                                    __('removing that %s directory', array('<code>' . basename($installURL) . '</code>')) .
                                                '</a>')));
                $div->appendChild($h3);

                if (!empty($postNotes)) {
                    $div->appendChild($div2);
                }
                $div->appendChild($p1);
                $div->appendChild($p2);

            } else {
                $div = new XMLElement('div');
                $h3 = new XMLElement('h3', __('Updating Failure'));
                $p = new XMLElement('p', __('An error occurred while updating Symphony.'));

                // Attempt to get update information from the log file
                try {
                    $log = file_get_contents($installLog . '/update');
                } catch (Exception $ex) {
                    $log_entry = Symphony::Log()->popFromLog();
                    if (isset($log_entry['message'])) {
                        $log = $log_entry['message'];
                    } else {
                        $log = 'Unknown error occurred when reading the update log';
                    }
                }

                $code = new XMLElement('code', $log);

                $div->appendChild($h3);
                $div->appendChild($p);
                $div->appendChild(
                    new XMLElement('pre', $code)
                );

                if (!empty($postNotes)) {
                    $div->appendChild($div2);
                }

            }

            $group->appendChild($div);

            $this->Form->appendChild($group);

            /**
            * Build the action button
            */
            $action = new XMLElement('div');
            $action->setAttribute('class', 'actions');

            $version = new XMLElement('p', 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'), array(
                'id' => 'version'
            ));
            $action->appendChild($version);

            $attr = array('accesskey' => 's');
            $action->appendChild(Widget::Input('action[complete]', __('Complete'), 'submit', $attr));

            $this->Form->appendChild($action);

        } else {
#var_dump($migrations);
            $migrations = $this->getMigrations();

            if (!empty($migrations)) {
                // Loop over all available migrations showing there
                // pre update notes.
                $preNotes = $this->getPreNotes($migrations);
            }

            $latestVersion = !empty($migrations)
                ? array_keys($migrations)[count($migrations)-1]
                : null;

            if (!empty($migrations)) {
                // Get the Release notes from the latest Version
                $rn = call_user_func(array(end($migrations), 'getReleaseNotes'));
            }

            // Create group
            $group = new XMLElement('fieldset');
            $group->setAttribute('class', 'settings');
            $group->appendChild(new XMLElement('legend', __('Update Symphony')));

            $helpVersion = isset($latestVersion) ? $latestVersion : $currentVersion;
            if (!empty($migrations)) {
                $helpLink = '<br /><a href="' . $rn . '" target="_blank" rel="noopener">Release Notes</a>';
            } else {
                $helpLink = '';
            }

            $pVersion = new XMLElement('p', 'Version ' . $helpVersion . $helpLink, array('class' => 'help'));
            $group->appendChild($pVersion);

            $div = new XMLElement('div');
            if (!empty($migrations)) {
                $h3 = new XMLElement('h3', __('Updating Symphony'));
                $p = new XMLElement('p', __('This script will update your existing Symphony installation to version %s.', array('<code>'. $latestVersion . '</code>')));

                if (!empty($preNotes)) {
                    $div2 = new XMLElement('div');
                    $h4 = new XMLElement('h4', __('Pre-Installation-Notes:'));
                    $div2->appendChild($h4);

                    $dl = new XMLElement('dl', null, array('class' => 'pre-installation-notes'));
                    foreach ($preNotes as $version => $notes) {
                        $dl->appendChild(new XMLElement('dt', $version, array('class' => 'help')));
                        $dd = new XMLElement('dd');
                        $ul = new XMLElement('ul');
                        foreach ($notes as $note) {
                            $ul->appendChild(new XMLElement('li', $note));
                        }
                        $dd->appendChild($ul);
                        $dl->appendChild($dd);
                    }
                    $div2->appendChild($dl);
                }
            } else {
                $h3 = new XMLElement('h3', __('Symphony is already up-to-date'));
                $p = new XMLElement('p', __('No available updates detected. It appears that Symphony is up to date.'));
            }

            $div->appendChild($h3);
            $div->appendChild($p);
            if (isset($div2) && $div2 !== null) {
                $div->appendChild($div2);
            }

            // Append div to group
            $group->appendChild($div);

            // Append group to form
            $this->Form->appendChild($group);

            /**
             * Build the action button
             */
            if (!empty($migrations)) {
                $action = new XMLElement('div');
                $action->setAttribute('class', 'actions');

                $version = new XMLElement('p', 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'), array(
                    'id' => 'version'
                ));
                $action->appendChild($version);

                $attr = array('accesskey' => 's');
                $action->appendChild(Widget::Input('action[update]', __('Update Symphony'), 'submit', $attr));

                $this->Form->appendChild($action);
            }
        }
    }

    private function getMigrations(): array
    {
        $installPath = DOCROOT . '/install';

        // Get available migrations. This will only contain the migrations
        // that are applicable to the current install.
        $migrations = array();

        if (is_dir($installPath . '/migrations')) {
            foreach (new DirectoryIterator($installPath . '/migrations') as $m) {
                if ($m->isDot() || $m->isDir() || General::getExtension($m->getFilename()) !== 'php') {
                    continue;
                }

                $version = str_replace('.php', '', $m->getFilename());

                // Include migration so we can see what the version is
                include_once($m->getPathname());
                $classname = 'migration_' . str_replace('.', '', $version);

                $m = new $classname();

                if (version_compare(Symphony::Configuration()->get('version', 'symphony'), call_user_func(array($m, 'getVersion')), '<')) {
                    $migrations[call_user_func(array($m, 'getVersion'))] = $m;
                }
            }

            // The DirectoryIterator may return files in a sporatic order
            // on different servers. This will ensure the array is sorted
            // correctly using `version_compare`
            uksort($migrations, 'version_compare');
        }

        return $migrations;
    }

    private function getPreNotes($migrations): array
    {
        $preNotes = array();

        foreach ($migrations as $version => $m) {
            $n = call_user_func(array($m, 'preUpdateNotes'));
            if (!empty($n)) {
                $preNotes[$version] = $n;
            }
        }

        return $preNotes;
    }

    private function getPostNotes($migrations): array
    {
        $postNotes = array();

        foreach ($migrations as $version => $m) {
            $n = call_user_func(array($m, 'postUpdateNotes'));
            if (!empty($n)) {
                $postNotes[$version] = $n;
            }
        }

        return $postNotes;
    }
}
