<?php

/**
 * @package content
 */

class InstallerPage extends HTMLPage
{
    private $_template;

    protected $_params;

    protected $_page_title;

    public function __construct($template, $params = array())
    {
        parent::__construct();

        $this->_template = $template;
        $this->_params = $params;

        $this->_page_title = __('Install Symphony');
    }

    public function generate($page = null)
    {
        $this->Html->setDTD('<!DOCTYPE html>');
        $this->Html->setAttribute('lang', Lang::get());

        $this->addHeaderToPage('Cache-Control', 'no-cache, must-revalidate, max-age=0');
        $this->addHeaderToPage('Expires', 'Mon, 12 Dec 1982 06:14:00 GMT');
        $this->addHeaderToPage('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $this->addHeaderToPage('Pragma', 'no-cache');

        $this->setTitle($this->_page_title);
        $this->addElementToHead(new XMLElement('meta', null, array('charset' => 'UTF-8')), 1);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'robots', 'content' => 'noindex')), 2);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1')), 3);

        $this->addStylesheetToHead(APPLICATION_URL . '/assets/css/installer.min.css', 'screen', 30);

        return parent::generate($page);
    }

    protected function __build($version = VERSION, XMLElement $extra = null)
    {
        parent::__build();

        $this->Form = Widget::Form(INSTALL_URL . '/index.php', 'post');
        $title = new XMLElement('h1', $this->_page_title);
        $version = new XMLElement('em', __('Version %s', array($version)));

        $title->appendChild($version);

        if (!is_null($extra)) {
            $title->appendChild($extra);
        }

        $this->Form->appendChild($title);

        if (isset($this->_params['show-languages']) && $this->_params['show-languages']) {
            $languages = new XMLElement('ul');

            foreach (Lang::getAvailableLanguages(false) as $code => $lang) {
                $languages->appendChild(new XMLElement(
                    'li',
                    Widget::Anchor(
                        $lang,
                        '?lang=' . $code
                    ),
                    ($_REQUEST['lang'] == $code || ($_REQUEST['lang'] == null && $code == 'en')) ? array('class' => 'selected') : array()
                ));
            }

            $languages->appendChild(new XMLElement(
                'li',
                Widget::Anchor(
                    __('Symphony is also available in other languages'),
                    'http://getsymphony.com/download/extensions/translations/'
                ),
                array('class' => 'more')
            ));

            $this->Form->appendChild($languages);
        }

        $main = new XMLElement('main');
        $main->appendChild($this->Form);
        $this->Body->appendChild($main);
        //$this->Body->appendChild($this->Form);

        $function = 'view' . str_replace('-', '', ucfirst($this->_template));
        $this->$function();
    }

    protected function viewMissinglog()
    {
        $h2 = new XMLElement('h2', __('Missing log file'));

        // What folder wasn't writable? The docroot or the logs folder?
        // RE: #1706
        if (is_writeable(DOCROOT) === false) {
            $folder = DOCROOT;
        } elseif (is_writeable(MANIFEST) === false) {
            $folder = MANIFEST;
        } elseif (is_writeable(INSTALL_LOGS) === false) {
            $folder = INSTALL_LOGS;
        }

        $p = new XMLElement('p', __('Symphony tried to create a log file and failed. Make sure the %s folder is writable.', array('<code>' . $folder . '</code>')));

        $this->Form->appendChild($h2);
        $this->Form->appendChild($p);
        $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
    }

    protected function viewRequirements()
    {
        $h2 = new XMLElement('h2', __('System Requirements'));

        $this->Form->appendChild($h2);

        if (!empty($this->_params['errors'])) {
            $div = new XMLElement('div');
            $this->__appendError(array_keys($this->_params['errors']), $div, __('Symphony needs the following requirements to be met before things can be taken to the “next level”.'));

            $this->Form->appendChild($div);
        }
        $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
    }

    protected function viewLanguages()
    {
        $h2 = new XMLElement('h2', __('Language selection'));
        $p = new XMLElement('p', __('This installation can speak in different languages. Which one are you fluent in?'));

        $this->Form->appendChild($h2);
        $this->Form->appendChild($p);

        $languages = array();

        foreach (Lang::getAvailableLanguages(false) as $code => $lang) {
            $languages[] = array($code, ($code === 'en'), $lang);
        }

        if (count($languages) > 1) {
            $languages[0][1] = false;
            $languages[1][1] = true;
        }

        $this->Form->appendChild(Widget::Select('lang', $languages));

        $Submit = new XMLElement('div', null, array('class' => 'actions submit'));
        $Submit->appendChild(Widget::Input('action[proceed]', __('Proceed with installation'), 'submit'));

        $this->Form->appendChild($Submit);
    }

    protected function viewFailure()
    {
        $h2 = new XMLElement('h2', __('Installation Failure'));
        $p = new XMLElement('p', __('An error occurred during installation.'));

        // Attempt to get log information from the log file
        try {
            $log = file_get_contents(INSTALL_LOGS . '/install');
        } catch (Exception $ex) {
            $log_entry = Symphony::Log()->popFromLog();
            if (isset($log_entry['message'])) {
                $log = $log_entry['message'];
            } else {
                $log = 'Unknown error occurred when reading the install log';
            }
        }

        $code = new XMLElement('code', $log);

        $this->Form->appendChild($h2);
        $this->Form->appendChild($p);
        $this->Form->appendChild(
            new XMLElement('pre', $code)
        );
        $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
    }

    protected function viewSuccess()
    {
        $symphonyUrl = URL . '/' . Symphony::Configuration()->get('admin-path', 'symphony');
        $this->Form->setAttribute('action', $symphonyUrl);

        $div = new XMLElement('div');
        $div->appendChild(
            new XMLElement('h2', __('The floor is yours'))
        );
        $div->appendChild(
            new XMLElement('p', __('Thanks for taking the quick, yet epic installation journey with us. It’s now your turn to shine!'))
        );
        $div->appendChild(
            new XMLElement('h3', __('📧 Note on email delivery'))
        );
        $div->appendChild(
            new XMLElement('p', __('Sym8 uses sendmail by default for sending emails. For productive environments, we strongly recommend switching to SMTP - e.g. via your hosting provider or an external mailbox provider.'))
        );
        $div->appendChild(
            new XMLElement('p', __('Many mailbox providers classify emails sent via sendmail as potentially unsafe and often deliver them to the spam folder.'))
        );
        $this->Form->appendChild($div);

        $ul = new XMLElement('ul');
        foreach ($this->_params['disabled-extensions'] as $handle) {
            $ul->appendChild(
                new XMLElement('li', '<code>' . $handle . '</code>')
            );
        }

        if ($ul->getNumberOfChildren() !== 0) {
            $this->Form->appendChild(
                new XMLElement('h3', __('📍 Note on the extensions'))
            );
            $this->Form->appendChild(
                new XMLElement('p',
                    __('Some extensions were intentionally not enabled during install. Please check the following extensions and install them manually:')
                )
            );
            $this->Form->appendChild($ul);
        }
        $this->Form->appendChild(
            new XMLElement('h3', __('⚠️ Remove installer'))
        );
        $this->Form->appendChild(
            new XMLElement('p',
                __('I think you and I will achieve great things together. Just one last thing: please %s to secure the safety of our relationship.', array(
                        '<a href="' . URL . '/install/?action=remove">' .
                        __('remove the %s folder', array('<code>' . basename(INSTALL) . '</code>')) .
                        '</a>'
                    )
                )
            )
        );

        $submit = new XMLElement('div', null, array('class' => 'actions submit'));
        $submit->appendChild(Widget::Input('submit', __('Okay, now take me to the login page'), 'submit'));

        $this->Form->appendChild($submit);
    }

    protected function viewConfiguration()
    {
        /* -----------------------------------------------
         * Populating fields array
         * -----------------------------------------------
        */

        $fields = isset($_POST['fields']) ? $_POST['fields'] : $this->_params['default-config'];

        /* -----------------------------------------------
         * Welcome
         * -----------------------------------------------
         */
        $fieldset = new XMLElement('fieldset');
        $fieldset->appendChild(
            new XMLElement('legend', __('Welcome'))
        );
        $fieldset->appendChild(
            new XMLElement('p', __('Think of this as a pre-game warm up. You know you’re going to kick-ass, so you’re savouring every moment before the show. Welcome to the Symphony install page.'))
        );

        $this->Form->appendChild($fieldset);

        if (!empty($this->_params['errors'])) {
            $this->Form->appendChild(
                Widget::Error(new XMLElement('p'), __('Oops, a minor hurdle on your path to glory! There appears to be something wrong with the details entered below.'))
            );
        }

        /* -----------------------------------------------
         * Environment settings
         * -----------------------------------------------
         */

        // Fresh installation:
        // Prevent browsers from suggesting or autofilling previous form values.
        $this->Form->setAttribute('autocomplete', 'off');

        $fieldset = new XMLElement('fieldset');
        $div = new XMLElement('div');
        $this->__appendError(array('no-write-permission-root', 'no-write-permission-workspace'), $div);
        if ($div->getNumberOfChildren() > 0) {
            $fieldset->appendChild($div);
            $this->Form->appendChild($fieldset);
        }

        /* -----------------------------------------------
         * Website & Locale settings
         * -----------------------------------------------
         */

        // --- Email placeholder setup ---
        $host = $_SERVER['HTTP_HOST'] ?? 'example.net';
        // remove possible port (e.g. :8080)
        $domain = preg_replace('/:\d+$/', '', $host);
        $domain = filter_var($domain, FILTER_SANITIZE_URL);

        $Environment = new XMLElement('fieldset');
        $Environment->appendChild(new XMLElement('legend', __('Website Preferences')));

        $label = Widget::Label(__('Name'));
        $input = Widget::Input('fields[general][sitename]', $fields['general']['sitename']);
        $input->setAttribute('required', 'required');
        $label->appendChild($input);

        $this->__appendError(array('general-no-sitename'), $label);
        $Environment->appendChild($label);

        $label = Widget::Label(__('Email address (for outgoing emails)'));
        $input = Widget::Input('fields[email_sendmail][from_address]', $fields['email_sendmail']['from_address'], 'email');
        $input->setAttribute('placeholder', 'notifications@' . $domain);
        $input->setAttribute('autocapitalize', 'none');
        $input->setAttribute('required', 'required');
        $label->appendChild($input);

        $this->__appendError(array('mail-no-from-address'), $label);
        $Environment->appendChild($label);

        $label = Widget::Label(__('Admin Path'));
        $input = Widget::Input('fields[symphony][admin-path]', $fields['symphony']['admin-path']);
        $input->setAttribute('required', 'required');
        $label->appendChild($input);

        $this->__appendError(array('no-symphony-path'), $label);
        $Environment->appendChild($label);

        $Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
        $Fieldset->appendChild(new XMLElement('legend', __('Date and Time')));
        $Fieldset->appendChild(new XMLElement('p', __('Customise how Date and Time values are displayed throughout the Administration interface.')));

        // Timezones
        $options = DateTimeObj::getTimezonesSelectOptions((
            isset($fields['region']['timezone']) && !empty($fields['region']['timezone'])
                ? $fields['region']['timezone']
                : date_default_timezone_get()
        ));
        $Fieldset->appendChild(Widget::Label(__('Region'), Widget::Select('fields[region][timezone]', $options)));

        $Div = new XMLElement('div', null, array('class' => 'two columns'));
        // Date formats
        $options = DateTimeObj::getDateFormatsSelectOptions($fields['region']['date_format']);
        // $Fieldset->appendChild(Widget::Label(__('Date Format'), Widget::Select('fields[region][date_format]', $options)));
        $Div->appendChild(Widget::Label(__('Date Format'), Widget::Select('fields[region][date_format]', $options), 'column'));

        // Time formats
        $options = DateTimeObj::getTimeFormatsSelectOptions($fields['region']['time_format']);
        // $Fieldset->appendChild(Widget::Label(__('Time Format'), Widget::Select('fields[region][time_format]', $options)));
        $Div->appendChild(Widget::Label(__('Time Format'), Widget::Select('fields[region][time_format]', $options), 'column'));
        $Fieldset->appendChild($Div);

        $Environment->appendChild($Fieldset);
        $this->Form->appendChild($Environment);

        /* -----------------------------------------------
         * Database settings
         * -----------------------------------------------
         */

        $Database = new XMLElement('fieldset');
        $Database->appendChild(new XMLElement('legend', __('Database Connection')));
        $Database->appendChild(new XMLElement('p', __('Please provide Symphony with access to a database.')));

        // Database name
        $label = Widget::Label(__('Database Name'));
        $input = Widget::Input('fields[database][db]', $fields['database']['db']);
        $input->setAttribute('required', 'required');
        $label->appendChild($input);

        $this->__appendError(array('database-incorrect-version', 'unknown-database', 'database-no-dbname'), $label);
        $Database->appendChild($label);

        // Database credentials
        $Div = new XMLElement('div', null, array('class' => 'two columns'));

        // $Div->appendChild(Widget::Label(__('Username'), Widget::Input('fields[database][user]', $fields['database']['user']), 'column'));
        $label = Widget::Label(__('Username'), null, 'column');
        $input = Widget::Input('fields[database][user]', $fields['database']['user']);
        $input->setAttribute('required', 'required');
        $label->appendChild($input);
        $Div->appendChild($label);

        // $Div->appendChild(Widget::Label(__('Password'), Widget::Input('fields[database][password]', $fields['database']['password'], 'password'), 'column'));
        $label = Widget::Label(__('Password'), null, 'column');
        $input = Widget::Input('fields[database][password]', $fields['database']['password'], 'password');
        $input->setAttribute('required', 'required');
        $label->appendChild($input);
        $Div->appendChild($label);

        $this->__appendError(array('database-invalid-credentials'), $Div);
        $Database->appendChild($Div);

        // Advanced configuration
        $Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
        $Fieldset->appendChild(new XMLElement('legend', __('Advanced Database Configuration')));
        $Fieldset->appendChild(new XMLElement('p', __('Leave these fields unless you are sure they need to be changed.')));

        // Advanced configuration: Host, Port
        $Div = new XMLElement('div', null, array('class' => 'two columns'));

        // $Div->appendChild(Widget::Label(__('Host'), Widget::Input('fields[database][host]', $fields['database']['host']), 'column'));
        $label = Widget::Label(__('Host'), null, 'column');
        $input = Widget::Input('fields[database][host]', $fields['database']['host']);
        $input->setAttribute('required', 'required');
        $label->appendChild($input);
        $Div->appendChild($label);

        // Advanced configuration: Table Prefix
        // $label = Widget::Label(__('Table Prefix'), Widget::Input('fields[database][tbl_prefix]', $fields['database']['tbl_prefix']));
        $label = Widget::Label(__('Table Prefix'), null, 'column');
        $input = Widget::Input('fields[database][tbl_prefix]', $fields['database']['tbl_prefix']);
        $input->setAttribute('required', 'required');
        $label->appendChild($input);
        $Div->appendChild($label);

        $this->__appendError(array('database-table-prefix', 'no-database-connection'), $Div);
        $Fieldset->appendChild($Div);

        // $Div->appendChild(Widget::Label(__('Port'), Widget::Input('fields[database][port]', $fields['database']['port'], 'number'), 'column'));
        // Sym8 automatically uses port 3306. You can define a different port in config.php after installation.
        $input = Widget::Input('fields[database][port]', $fields['database']['port'], 'hidden');
        $Fieldset->appendChild($input);

        $Database->appendChild($Fieldset);
        $this->Form->appendChild($Database);

        /* -----------------------------------------------
         * Permission settings
         * -----------------------------------------------
         */

        // $Permissions = new XMLElement('fieldset');
        // $Permissions->appendChild(new XMLElement('legend', __('Permission Settings')));
        // $Permissions->appendChild(new XMLElement('p', __('Set the permissions Symphony uses when saving files/directories.')));

        // $Div = new XMLElement('div', null, array('class' => 'two columns'));
        // $Div->appendChild(Widget::Label(__('Files'), Widget::Input('fields[file][write_mode]', $fields['file']['write_mode']), 'column'));
        // $Div->appendChild(Widget::Label(__('Directories'), Widget::Input('fields[directory][write_mode]', $fields['directory']['write_mode']), 'column'));

        // $Permissions->appendChild($Div);
        // $this->Form->appendChild($Permissions);

        // Pass these values as hidden fields to keep the installer clean and lean.
        // These values are now standard on modern vHosts.
        // by tiloschroeder
        $hiddenFilePermission = Widget::Input('fields[file][write_mode]', $fields['file']['write_mode'], 'hidden');
        $hiddenDirPermission = Widget::Input('fields[directory][write_mode]', $fields['directory']['write_mode'], 'hidden');
        $this->Form->appendChild($hiddenFilePermission);
        $this->Form->appendChild($hiddenDirPermission);

        /* -----------------------------------------------
         * User settings
         * -----------------------------------------------
         */

        $User = new XMLElement('fieldset');
        $User->appendChild(new XMLElement('legend', __('User Information')));
        $User->appendChild(new XMLElement('p', __('Once installation is complete, you will be able to log in to the Symphony admin area with these user details as <strong>Super User</strong> (aka Developer).')));

        $fields['user'] = $fields['user'] ?? null;
        // Username
        $fields['user']['username'] = $fields['user']['username'] ?? null;
        // $label = Widget::Label(__('Username'), Widget::Input('fields[user][username]', $fields['user']['username']));
        $label = Widget::Label(__('Username'));
        $input = Widget::Input('fields[user][username]', $fields['user']['username']);
        $input->setAttribute('required', 'required');
        $label->appendChild($input);

        $this->__appendError(array('user-no-username'), $label);
        $User->appendChild($label);

        // Password
        $fields['user']['password'] = $fields['user']['password'] ?? null;
        $fields['user']['confirm-password'] = $fields['user']['confirm-password'] ?? null;
        $Div = new XMLElement('div', null, array('class' => 'two columns'));

        // $Div->appendChild(Widget::Label(__('Password'), Widget::Input('fields[user][password]', $fields['user']['password'], 'password'), 'column'));
        $label = Widget::Label(__('Password'), null, 'column');
        $input = Widget::Input('fields[user][password]', $fields['user']['password'], 'password');
        $input->setAttribute('autocomplete', 'new-password');
        $input->setAttribute('spellcheck', 'false');
        $input->setAttribute('required', 'required');
        $label->appendChild($input);
        $Div->appendChild($label);

        // $Div->appendChild(Widget::Label(__('Confirm Password'), Widget::Input('fields[user][confirm-password]', $fields['user']['confirm-password'], 'password'), 'column'));
        $label = Widget::Label(__('Confirm Password'), null, 'column');
        $input = Widget::Input('fields[user][confirm-password]', $fields['user']['confirm-password'], 'password');
        $input->setAttribute('autocomplete', 'new-password');
        $input->setAttribute('spellcheck', 'false');
        $input->setAttribute('required', 'required');
        $label->appendChild($input);
        $Div->appendChild($label);

        $this->__appendError(array('user-no-password', 'user-password-mismatch'), $Div);
        $User->appendChild($Div);

        // Personal information
        $Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
        $Fieldset->appendChild(new XMLElement('legend', __('Personal Information')));
        $Fieldset->appendChild(new XMLElement('p', __('Please add the following personal details for this user.')));

        // Personal information: First Name, Last Name
        $fields['user']['firstname'] = $fields['user']['firstname'] ?? null;
        $fields['user']['lastname'] = $fields['user']['lastname'] ?? null;
        $Div = new XMLElement('div', null, array('class' => 'two columns'));

        // $Div->appendChild(Widget::Label(__('First Name'), Widget::Input('fields[user][firstname]', $fields['user']['firstname']), 'column'));
        $label = Widget::Label(__('First Name'), null, 'column');
        $input = Widget::Input('fields[user][firstname]', $fields['user']['firstname']);
        $input->setAttribute('autocomplete', 'given-name');
        $input->setAttribute('required', 'required');
        $label->appendChild($input);
        $Div->appendChild($label);

        // $Div->appendChild(Widget::Label(__('Last Name'), Widget::Input('fields[user][lastname]', $fields['user']['lastname']), 'column'));
        $label = Widget::Label(__('Last Name'), null, 'column');
        $input = Widget::Input('fields[user][lastname]', $fields['user']['lastname']);
        $input->setAttribute('autocomplete', 'family-name');
        $input->setAttribute('required', 'required');
        $label->appendChild($input);
        $Div->appendChild($label);

        $this->__appendError(array('user-no-name'), $Div);
        $Fieldset->appendChild($Div);

        // Personal information: Email Address
        $fields['user']['email'] = $fields['user']['email'] ?? null;
        $label = Widget::Label(__('Email Address'));

        $input = Widget::Input('fields[user][email]', $fields['user']['email'], 'email');
        $input->setAttribute('placeholder', 'firstname.lastname@' . $domain);
        $input->setAttribute('autocomplete', 'email');
        $input->setAttribute('autocapitalize', 'none');
        $input->setAttribute('required', 'required');
        $label->appendChild($input);

        $this->__appendError(array('user-invalid-email'), $label);
        $Fieldset->appendChild($label);

        $User->appendChild($Fieldset);
        $this->Form->appendChild($User);

        /* -----------------------------------------------
         * Submit area
         * -----------------------------------------------
         */

        $fieldset = new XMLElement('fieldset');
        $fieldset->appendChild(new XMLElement('legend', __('Install Symphony')));
        $fieldset->appendChild(new XMLElement('p', __('The installation process goes by really quickly. Make sure to take a deep breath before you press that sweet button.', array('<code>' . basename(INSTALL_URL) . '</code>'))));
        $this->Form->appendChild($fieldset);

        $Submit = new XMLElement('div', null, array('class' => 'actions submit'));
        $Submit->appendChild(Widget::Input('lang', Lang::get(), 'hidden'));

        $Submit->appendChild(Widget::Input('action[install]', __('Install Symphony'), 'submit'));

        $this->Form->appendChild($Submit);

        // Set the header status `400` only if there are errors.
        // Avoids browsers misinterpreting the page as failed load.
        // by tiloschroeder
        // if (isset($this->_params['errors'])) {
        if (!empty($this->_params['errors'])) {
            $this->setHttpStatus(Page::HTTP_STATUS_BAD_REQUEST);
        }
    }

    private function __appendError(array $codes, XMLElement &$element, $message = null)
    {
        if (is_null($message)) {
            $message =  __('The following errors have been reported:');
        }

        foreach ($codes as $i => $c) {
            if (!isset($this->_params['errors'][$c])) {
                unset($codes[$i]);
            }
        }

        if (!empty($codes)) {
            $ul = new XMLElement('ul');

            foreach ($codes as $c) {
                if (isset($this->_params['errors'][$c])) {
                    $li = new XMLElement('li');

                    $h3 = new XMLElement('h3', $this->_params['errors'][$c]['msg']);
                    $li->appendChild($h3);

                    $p = new XMLElement('p', $this->_params['errors'][$c]['details']);
                    $li->appendChild($p);

                    $ul->appendChild($li);
                }
            }

            $element = Widget::Error($element, $message);
            $element->appendChild($ul);
        }
    }
}
