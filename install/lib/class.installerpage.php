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
        $htmlDir = 'ltr';
        if ((Lang::get() === 'ar' || Lang::get() === 'he' || Lang::get() === 'fa')) {
            $htmlDir = 'rtl';
        }
        $this->Html->setAttribute('dir', $htmlDir);
        // For testing light/dark theme only
        // $this->Html->setAttribute('data-theme', 'light');

        $this->addHeaderToPage('Cache-Control', 'no-cache, must-revalidate, max-age=0');
        $this->addHeaderToPage('Expires', 'Mon, 12 Dec 1982 06:14:00 GMT');
        $this->addHeaderToPage('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $this->addHeaderToPage('Pragma', 'no-cache');

        $this->setTitle($this->_page_title);
        $this->addElementToHead(new XMLElement('meta', null, array('charset' => 'UTF-8')), 1);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'robots', 'content' => 'noindex, nofollow')), 2);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1')), 3);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'color-scheme', 'content' => 'dark light')), 4);

        // $this->addStylesheetToHead(APPLICATION_URL . '/assets/css/installer.min.css', 'screen', 30);
        $this->addStylesheetToHead(APPLICATION_URL . '/assets/css/pico.min.css', 'screen', null, false, true);
        $this->addStylesheetToHead(APPLICATION_URL . '/assets/css/pico-install.css', 'screen', null, false, true);
        $this->addStylesheetToHead(APPLICATION_URL . '/assets/css/pico-messages.css', 'screen', null, false, true);
        $this->addScriptToHead(APPLICATION_URL . '/assets/js/lang-select.js', null, false, true, false);
        $this->addScriptToHead(APPLICATION_URL . '/assets/js/install.js', null, false, true, false);

        return parent::generate($page);
    }

    protected function __build($version = VERSION, XMLElement $extra = null)
    {
        parent::__build();

        $this->Form = Widget::Form(INSTALL_URL . '/index.php', 'post');

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
        $main->setAttribute('class', 'container');

        $title = new XMLElement('h1', $this->_page_title);
        $version = new XMLElement('em', __('Version %s', array($version)));

        $title->appendChild($version);

        if (!is_null($extra)) {
            $title->appendChild($extra);
        }

        $main->appendChild($title);

        $main->appendChild($this->Form);

        $this->Body->appendChild($main);
        //$this->Body->appendChild($this->Form);

        $function = 'view' . str_replace('-', '', ucfirst($this->_template));
        $this->$function();
    }

    protected function viewMissinglog()
    {
        $h2 = new XMLElement('h2', __('Missing log file'), array('class' => 'message-heading'));

        // What folder wasn't writable? The docroot or the logs folder?
        // RE: #1706
        if (is_writeable(DOCROOT) === false) {
            $folder = DOCROOT;
        } elseif (is_writeable(MANIFEST) === false) {
            $folder = MANIFEST;
        } elseif (is_writeable(INSTALL_LOGS) === false) {
            $folder = INSTALL_LOGS;
        }

        $div = new XMLElement('div', null, array('class' => 'message error-message', 'role' => 'alert'));
        $div->appendChild($h2);
        $div->appendChild(new XMLElement(
                            'p',
                            __('Symphony tried to create a log file and failed. Make sure the folder %s is writable.', array('<code>' . $folder . '</code>')),
                            array('class' => 'message-details')
                        ));
        $this->Form->appendChild($div);
        $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
    }

    protected function viewRequirements()
    {
        $h2 = new XMLElement('h2', __('System Requirements'), array('class' => 'form-heading'));

        $this->Form->appendChild($h2);

        if (!empty($this->_params['errors'])) {
            $div = new XMLElement('div');
            $div->appendChild(new XMLElement(
                                'p',
                                __('Symphony requires the following conditions to be met before the installation can continue.')
                            ));
            $this->__appendMessage(array_keys($this->_params['errors']), $div);

            $this->Form->appendChild($div);
        }
        $this->setHttpStatus(Page::HTTP_STATUS_ERROR);
    }

    protected function viewLanguages()
    {
        $h2 = new XMLElement('h2', __('Language selection'), array('class' => 'form-heading', 'id' => 'installer-title'));
        $p = new XMLElement('p', __('This installation can speak in different languages. Which one are you fluent in?'), array('id' => 'installer-intro'));

        $this->Form->appendChild($h2);
        $this->Form->appendChild($p);

        $languages = array();

        foreach (Lang::getAvailableLanguages(false) as $code => $lang) {
            $languages[] = array($code, ($code === 'en'), $lang);
        }

        asort($languages);

        $this->Form->appendChild(Widget::Select('lang', $languages));

        $Submit = new XMLElement('div', null, array('class' => 'language actions submit'));
        $Submit->appendChild(Widget::Input('action[proceed]', __('Proceed with installation'), 'submit', array('id' => 'installer-submit')));

        $this->Form->appendChild($Submit);
    }

    protected function viewFailure()
    {
        $h2 = new XMLElement('h2', __('Installation Failure'), array('class' => 'form-heading'));
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

        // Success message
        $div = new XMLElement('div', null, array('class' => 'message success-message', 'role' => 'alert'));
        $div->appendChild(new XMLElement('h2', __('The floor is yours'), array('class' => 'message-heading')));
        $div->appendChild(new XMLElement('p', __('Your site is ready to go. Welcome to Symphony – have fun creating!'), array('class' => 'message-details')));
        $this->Form->appendChild($div);

        // Note message for email delivery
        $div = new XMLElement('div', null, array('class' => 'message info-message'));
        $div->appendChild(new XMLElement('h3', __('📧 Note on email delivery'), array('class' => 'message-heading')));
        $div->appendChild(new XMLElement('p', __('Sym8 uses sendmail by default for sending emails. For productive environments, we strongly recommend switching to SMTP - e.g. via your hosting provider or an external mailbox provider.'), array('class' => 'message-details')));
        $div->appendChild(new XMLElement('p', __('Many mailbox providers classify emails sent via sendmail as potentially unsafe and often deliver them to the spam folder.'), array('class' => 'message-details')));
        $this->Form->appendChild($div);

        // Note message for non installed extensions
        $div = new XMLElement('div', null, array('class' => 'message info-message'));
        $ul = new XMLElement('ul');
        foreach ($this->_params['disabled-extensions'] as $handle) {
            $ul->appendChild(new XMLElement('li', '<code>' . $handle . '</code>'));
        }

        if ($ul->getNumberOfChildren() !== 0) {
            $div->appendChild(new XMLElement('h3', __('📍 Note on the extensions'), array('class' => 'message-heading')));
            $div->appendChild(new XMLElement('p', __('Some optional extensions were intentionally not enabled during install. You can activate them later in the backend.'), array('class' => 'message-details')));
            $this->Form->appendChild($div);
        }

        // Note message to remove installer
        $div = new XMLElement('div', null, array('class' => 'message info-message'));
        $div->appendChild(new XMLElement('h3', __('⚠️ Remove installer'), array('class' => 'message-heading')));
        $div->appendChild(new XMLElement('p', __('I think you and I will achieve great things together. Just one last thing: please %s to secure the safety of our relationship.', array('<a href="' . URL . '/install/?action=remove">' . __('remove the %s folder', array('<code>' . basename(INSTALL) . '</code>')) . '</a>')), array('class' => 'message-details')));
        $this->Form->appendChild($div);

        // Login button
        $submit = new XMLElement('div', null, array('class' => 'actions success submit'));
        $submit->appendChild(Widget::Input('submit', __('Symphony Login'), 'submit'));

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
        $fieldset = new XMLElement('fieldset', null, array('class' => 'fieldset-block'));
        $fieldset->appendChild(
            new XMLElement('legend', __('Welcome'))
        );
        $fieldset->appendChild(
            new XMLElement('p', __('We’ll walk you through the essential setup details so your site is ready to go in just a few minutes.'))
        );

        $this->Form->appendChild($fieldset);

        if (!empty($this->_params['errors'])) {
            $div = new XMLElement('div', null, array('class' => 'message error-message', 'role' => 'alert'));
            $div->appendChild(new XMLElement('p', __('Something went wrong.'), array('class' => 'message-heading')));
            $div->appendChild(new XMLElement('p', __('Please check the fields below — some details need your attention.'), array('class' => 'message-details')));
            $this->Form->appendChild($div);
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

        $Environment = new XMLElement('fieldset', null, array('class' => 'fieldset-block'));
        $Environment->appendChild(new XMLElement('legend', __('Website Preferences')));

        $div = new XMLElement('div', null, array('class' => 'form-field'));
        $label = Widget::Label(__('Site name'));
        $label->setAttribute('for', 'sitename');
        $input = Widget::Input('fields[general][sitename]', $fields['general']['sitename']);
        $input->setAttribute('id', 'sitename');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['general-no-sitename'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'helper-sitename');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }

        }
        $div->appendChild($label);
        $div->appendChild($input);

        $this->__appendError(array('general-no-sitename'), $div, null, 'helper-sitename');
        $Environment->appendChild($div);

        $div = new XMLElement('div', null, array('class' => 'form-field'));
        $label = Widget::Label(__('Email address (for outgoing emails)'));
        $label->setAttribute('for', 'from-address');
        $input = Widget::Input('fields[email_sendmail][from_address]', $fields['email_sendmail']['from_address'], 'email');
        $input->setAttribute('id', 'from-address');
        $input->setAttribute('placeholder', 'notifications@' . $domain);
        $input->setAttribute('autocapitalize', 'none');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['mail-no-from-address'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'helper-from-address');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $input->setAttribute('required', 'required');
        $div->appendChild($label);
        $div->appendChild($input);
        $this->__appendError(array('mail-no-from-address'), $div, null, 'helper-from-address');
        $Environment->appendChild($div);

        $div = new XMLElement('div', null, array('class' => 'form-field'));
        $label = Widget::Label(__('Admin Path'));
        $label->setAttribute('for', 'admin-path');
        $input = Widget::Input('fields[symphony][admin-path]', $fields['symphony']['admin-path']);
        $input->setAttribute('id', 'admin-path');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['no-symphony-path'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'helper-admin-path');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);

        $this->__appendError(array('no-symphony-path'), $div, null, 'helper-admin-path');
        $Environment->appendChild($div);

        $Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
        $Fieldset->appendChild(new XMLElement('legend', __('Date and Time')));
        $Fieldset->appendChild(new XMLElement('p', __('Customise how Date and Time values are displayed throughout the Administration interface.')));

        // Timezones
        $options = DateTimeObj::getTimezonesSelectOptions((
            isset($fields['region']['timezone']) && !empty($fields['region']['timezone'])
                ? $fields['region']['timezone']
                : date_default_timezone_get()
        ));

        $div = new XMLElement('div', null, array('class' => 'form-field'));
        $label = Widget::Label(__('Region'));
        $label->setAttribute('for', 'region');
        $select = Widget::Select('fields[region][timezone]', $options);
        $select->setAttribute('id', 'region');
        $div->appendChild($label);
        $div->appendChild($select);
        $Fieldset->appendChild($div);

        $Div = new XMLElement('div', null, array('class' => 'two columns'));
        // Date formats
        $options = DateTimeObj::getDateFormatsSelectOptions($fields['region']['date_format']);
        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('Date Format'));
        $label->setAttribute('for', 'date-format');
        $select = Widget::Select('fields[region][date_format]', $options);
        $select->setAttribute('id', 'date-format');
        $div->appendChild($label);
        $div->appendChild($select);
        $Div->appendChild($div);
        #$Div->appendChild(Widget::Label(__('Date Format'), Widget::Select('fields[region][date_format]', $options), 'column'));

        // Time formats
        $options = DateTimeObj::getTimeFormatsSelectOptions($fields['region']['time_format']);
        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('Time Format'));
        $label->setAttribute('for', 'time-format');
        $select = Widget::Select('fields[region][time_format]', $options);
        $select->setAttribute('id', 'time-format');
        $div->appendChild($label);
        $div->appendChild($select);
        $Div->appendChild($div);
        #$Div->appendChild(Widget::Label(__('Time Format'), Widget::Select('fields[region][time_format]', $options), 'column'));

        $Fieldset->appendChild($Div);

        $Environment->appendChild($Fieldset);
        $this->Form->appendChild($Environment);

        /* -----------------------------------------------
         * Database settings
         * -----------------------------------------------
         */

        $Database = new XMLElement('fieldset', null, array('class' => 'fieldset-block'));
        $Database->appendChild(new XMLElement('legend', __('Database Connection')));
        $Database->appendChild(new XMLElement('p', __('Please provide Symphony with access to a database.')));

        // Database name
        $div = new XMLElement('div', null, array('class' => 'form-field'));
        $label = Widget::Label(__('Database Name'));
        $label->setAttribute('for', 'database-name');
        $input = Widget::Input('fields[database][db]', $fields['database']['db']);
        $input->setAttribute('id', 'database-name');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (
                isset($this->_params['errors']['database-incorrect-version'])
                || isset($this->_params['errors']['unknown-database'])
                || isset($this->_params['errors']['database-no-dbname'])
            ) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'helper-database-name');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);
        #$label->appendChild($input);

        $this->__appendError(array('database-incorrect-version', 'unknown-database', 'database-no-dbname'), $div, null, 'helper-database-name');
        $Database->appendChild($div);

        // Database credentials
        $Div = new XMLElement('div', null, array('class' => 'two columns'));

        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('Username'));
        $label->setAttribute('for', 'database-user');
        $input = Widget::Input('fields[database][user]', $fields['database']['user']);
        $input->setAttribute('id', 'database-user');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['database-invalid-credentials'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'helper-database-user');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);
        #$label->appendChild($input);
        $Div->appendChild($div);

        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('Password'));
        $label->setAttribute('for', 'database-password');
        $div1 = new XMLElement('div', null, array('class' => 'input-group'));
        $input = Widget::Input('fields[database][password]', $fields['database']['password'], 'password');
        $input->setAttribute('id', 'database-password');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['database-invalid-credentials'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'helper-database-user');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $button = new XMLElement('button', null, array('aria-label' => __('Show password'), 'aria-pressed' => 'false', 'aria-controls' => 'database-password', 'data-label-show' => __('Show password'), 'data-label-hide' => __('Hide password'), 'type' => 'button', 'class' => 'show-hide-db-password secondary outline'));
        $button->setValue('<span class="icon-show">
                <!-- visibility -->
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 24 24" focusable="false"><path d="M15.188 14.688q1.313-1.312 1.313-3.187t-1.313-3.188-3.187-1.312-3.188 1.312-1.312 3.188 1.312 3.187 3.188 1.313 3.187-1.313m-5.1-1.275q-.787-.787-.787-1.912t.787-1.913 1.913-.787 1.912.787.788 1.913-.788 1.912-1.912.788-1.913-.788m-4.737 3.55q-3-2.037-4.35-5.462 1.35-3.425 4.35-5.463T12 4.001t6.65 2.037T23 11.501q-1.35 3.425-4.35 5.462T12 19.001t-6.65-2.038m11.837-1.45q2.363-1.487 3.613-4.012-1.25-2.525-3.613-4.013T12 6.001 6.812 7.488 3.2 11.501q1.25 2.525 3.612 4.012T12 17.001t5.187-1.488"/></svg>
            </span>
            <span class="icon-hide" hidden>
                <!-- visibility_off -->
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 24 24" focusable="false"><path d="m16.1 13.3-1.45-1.45q.225-1.175-.675-2.2t-2.325-.8L10.2 7.4q.425-.2.863-.3T12 7q1.875 0 3.188 1.313T16.5 11.5q0 .5-.1.938t-.3.862m3.2 3.15-1.45-1.4q.95-.725 1.688-1.588T20.8 11.5q-1.25-2.525-3.587-4.013T12 6q-.725 0-1.425.1T9.2 6.4L7.65 4.85q1.025-.425 2.1-.638T12 4q3.775 0 6.725 2.087T23 11.5q-.575 1.475-1.512 2.737T19.3 16.45m.5 6.15-4.2-4.15q-.875.275-1.762.413T12 19q-3.775 0-6.725-2.087T1 11.5q.525-1.325 1.325-2.462T4.15 7L1.4 4.2l1.4-1.4 18.4 18.4zM5.55 8.4q-.725.65-1.325 1.425T3.2 11.5q1.25 2.525 3.588 4.013T12 17q.5 0 .975-.062t.975-.138l-.9-.95q-.275.075-.525.113T12 16q-1.875 0-3.188-1.312T7.5 11.5q0-.275.037-.525t.113-.525z"/></svg>
            </span>');

        $div1->appendChild($input);
        $div1->appendChild($button);
        $div->appendChild($label);
        $div->appendChild($div1);
        #$label->appendChild($input);
        $Div->appendChild($div);

        $this->__appendError(array('database-invalid-credentials'), $Div, null, 'helper-database-user');
        $Database->appendChild($Div);

        // Advanced configuration
        $Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
        $Fieldset->appendChild(new XMLElement('legend', __('Advanced Database Configuration')));
        $Fieldset->appendChild(new XMLElement('p', __('Leave these fields unless you are sure they need to be changed.')));

        // Advanced configuration: Host, Port
        $Div = new XMLElement('div', null, array('class' => 'two columns'));

        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('Host'));
        $label->setAttribute('for', 'database-host');
        $input = Widget::Input('fields[database][host]', $fields['database']['host']);
        $input->setAttribute('id', 'database-host');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['no-database-connection'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'helper-database-connection');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);
        #$label->appendChild($input);
        $Div->appendChild($div);

        // Advanced configuration: Table Prefix
        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('Table Prefix'));
        $label->setAttribute('for', 'table-prefix');
        $input = Widget::Input('fields[database][tbl_prefix]', $fields['database']['tbl_prefix']);
        $input->setAttribute('id', 'table-prefix');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['database-table-prefix'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'helper-table-prefix');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);
        #$label->appendChild($input);
        $Div->appendChild($div);

        $this->__appendError(array('database-table-prefix'), $Div, null, 'helper-table-prefix');
        $this->__appendError(array('no-database-connection'), $Div, null, 'helper-database-connection');
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

        $User = new XMLElement('fieldset', null, array('class' => 'fieldset-block'));
        $User->appendChild(new XMLElement('legend', __('User Information')));
        $User->appendChild(new XMLElement('p', __('Once installation is complete, you will be able to log in to the Symphony admin area with these user details as <strong>Super User</strong> (aka Developer).')));

        $fields['user'] = $fields['user'] ?? null;
        // Username
        $fields['user']['username'] = $fields['user']['username'] ?? null;
        $div = new XMLElement('div', null, array('class' => 'form-field'));
        $label = Widget::Label(__('Username'));
        $label->setAttribute('for', 'username');
        $input = Widget::Input('fields[user][username]', $fields['user']['username']);
        $input->setAttribute('id', 'username');
        $input->setAttribute('autocapitalize', 'off');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['user-no-username'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'helper-no-username');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);
        #$label->appendChild($input);

        $this->__appendError(array('user-no-username'), $div, null, 'helper-no-username');
        $User->appendChild($div);

        // Password
        $fields['user']['password'] = $fields['user']['password'] ?? null;
        $fields['user']['confirm-password'] = $fields['user']['confirm-password'] ?? null;
        $Div = new XMLElement('div', null, array('class' => 'two columns'));

        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('Password'));
        $label->setAttribute('for', 'password');
        $div1 = new XMLElement('div', null, array('class' => 'input-group'));
        $input = Widget::Input('fields[user][password]', $fields['user']['password'], 'password');
        $input->setAttribute('id', 'password');
        $input->setAttribute('autocomplete', 'new-password');
        $input->setAttribute('spellcheck', 'false');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['user-no-password']) || isset($this->_params['errors']['user-password-mismatch'])) {
                $input->setAttribute('aria-invalid', 'true');
                if (isset($this->_params['errors']['user-no-password'])) {
                    $input->setAttribute('aria-describedby', 'user-no-password');
                } elseif (isset($this->_params['errors']['user-password-mismatch'])) {
                    $input->setAttribute('aria-describedby', 'user-password-mismatch');
                }
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $button = new XMLElement('button', null, array('aria-label' => __('Show passwords'), 'aria-pressed' => 'false', 'aria-controls' => 'password password-confirm', 'data-label-show' => __('Show passwords'), 'data-label-hide' => __('Hide passwords'), 'type' => 'button', 'class' => 'show-hide-password secondary outline'));
        $button->setValue('<span class="icon-show">
                <!-- visibility -->
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 24 24" focusable="false"><path d="M15.188 14.688q1.313-1.312 1.313-3.187t-1.313-3.188-3.187-1.312-3.188 1.312-1.312 3.188 1.312 3.187 3.188 1.313 3.187-1.313m-5.1-1.275q-.787-.787-.787-1.912t.787-1.913 1.913-.787 1.912.787.788 1.913-.788 1.912-1.912.788-1.913-.788m-4.737 3.55q-3-2.037-4.35-5.462 1.35-3.425 4.35-5.463T12 4.001t6.65 2.037T23 11.501q-1.35 3.425-4.35 5.462T12 19.001t-6.65-2.038m11.837-1.45q2.363-1.487 3.613-4.012-1.25-2.525-3.613-4.013T12 6.001 6.812 7.488 3.2 11.501q1.25 2.525 3.612 4.012T12 17.001t5.187-1.488"/></svg>
            </span>
            <span class="icon-hide" hidden>
                <!-- visibility_off -->
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 24 24" focusable="false"><path d="m16.1 13.3-1.45-1.45q.225-1.175-.675-2.2t-2.325-.8L10.2 7.4q.425-.2.863-.3T12 7q1.875 0 3.188 1.313T16.5 11.5q0 .5-.1.938t-.3.862m3.2 3.15-1.45-1.4q.95-.725 1.688-1.588T20.8 11.5q-1.25-2.525-3.587-4.013T12 6q-.725 0-1.425.1T9.2 6.4L7.65 4.85q1.025-.425 2.1-.638T12 4q3.775 0 6.725 2.087T23 11.5q-.575 1.475-1.512 2.737T19.3 16.45m.5 6.15-4.2-4.15q-.875.275-1.762.413T12 19q-3.775 0-6.725-2.087T1 11.5q.525-1.325 1.325-2.462T4.15 7L1.4 4.2l1.4-1.4 18.4 18.4zM5.55 8.4q-.725.65-1.325 1.425T3.2 11.5q1.25 2.525 3.588 4.013T12 17q.5 0 .975-.062t.975-.138l-.9-.95q-.275.075-.525.113T12 16q-1.875 0-3.188-1.312T7.5 11.5q0-.275.037-.525t.113-.525z"/></svg>
            </span>');
        $div1->appendChild($input);
        $div1->appendChild($button);
        $div->appendChild($label);
        $div->appendChild($div1);
        #$label->appendChild($input);
        $Div->appendChild($div);

        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('Confirm Password'));
        $label->setAttribute('for', 'password-confirm');
        $input = Widget::Input('fields[user][confirm-password]', $fields['user']['confirm-password'], 'password');
        $input->setAttribute('id', 'password-confirm');
        $input->setAttribute('autocomplete', 'new-password');
        $input->setAttribute('spellcheck', 'false');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['user-no-password']) || isset($this->_params['errors']['user-password-mismatch'])) {
                $input->setAttribute('aria-invalid', 'true');
                if (isset($this->_params['errors']['user-no-password'])) {
                    $input->setAttribute('aria-describedby', 'user-no-password');
                } elseif (isset($this->_params['errors']['user-password-mismatch'])) {
                    $input->setAttribute('aria-describedby', 'user-password-mismatch');
                }
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);
        #$label->appendChild($input);
        $Div->appendChild($div);

        $this->__appendError(array('user-no-password'), $Div, null, 'user-no-password');
        $this->__appendError(array('user-password-mismatch'), $Div, null, 'user-password-mismatch');
        $User->appendChild($Div);

        // Personal information
        $Fieldset = new XMLElement('fieldset', null, array('class' => 'frame'));
        $Fieldset->appendChild(new XMLElement('legend', __('Personal Information')));
        $Fieldset->appendChild(new XMLElement('p', __('Please add the following personal details for this user.')));

        // Personal information: First Name, Last Name
        $fields['user']['firstname'] = $fields['user']['firstname'] ?? null;
        $fields['user']['lastname'] = $fields['user']['lastname'] ?? null;
        $Div = new XMLElement('div', null, array('class' => 'two columns'));

        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('First Name'));
        $label->setAttribute('for', 'firstname');
        $input = Widget::Input('fields[user][firstname]', $fields['user']['firstname']);
        $input->setAttribute('id', 'firstname');
        $input->setAttribute('autocomplete', 'given-name');
        $input->setAttribute('autocapitalize', 'on');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['user-no-name'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'user-no-name');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);
        #$label->appendChild($input);
        $Div->appendChild($div);

        $div = new XMLElement('div', null, array('class' => 'form-field column'));
        $label = Widget::Label(__('Last Name'));
        $label->setAttribute('for', 'lastname');
        $input = Widget::Input('fields[user][lastname]', $fields['user']['lastname']);
        $input->setAttribute('id', 'lastname');
        $input->setAttribute('autocomplete', 'family-name');
        $input->setAttribute('autocapitalize', 'on');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['user-no-name'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'user-no-name');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);
        #$label->appendChild($input);
        $Div->appendChild($div);

        $this->__appendError(array('user-no-name'), $Div, null, 'user-no-name');
        $Fieldset->appendChild($Div);

        // Personal information: Email Address
        $fields['user']['email'] = $fields['user']['email'] ?? null;
        $div = new XMLElement('div', null, array('class' => 'form-field'));
        $label = Widget::Label(__('Email Address'));
        $label->setAttribute('for', 'user-email');
        $input = Widget::Input('fields[user][email]', $fields['user']['email'], 'email');
        $input->setAttribute('id', 'user-email');
        $input->setAttribute('placeholder', 'firstname.lastname@' . $domain);
        $input->setAttribute('autocomplete', 'email');
        $input->setAttribute('autocapitalize', 'off');
        $input->setAttribute('required', 'required');
        if (isset($_POST['fields'])) {
            if (isset($this->_params['errors']['user-invalid-email'])) {
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', 'user-invalid-email');
            } else {
                $input->setAttribute('aria-invalid', 'false');
            }
        }
        $div->appendChild($label);
        $div->appendChild($input);
        #$label->appendChild($input);

        $this->__appendError(array('user-invalid-email'), $div, null, 'user-invalid-email');
        $Fieldset->appendChild($div);

        $User->appendChild($Fieldset);
        $this->Form->appendChild($User);

        /* -----------------------------------------------
         * Submit area
         * -----------------------------------------------
         */

        $fieldset = new XMLElement('fieldset', null, array('class' => 'fieldset-block'));
        $fieldset->appendChild(new XMLElement('legend', __('Install Symphony')));

        $Div = new XMLElement('div', null, array('class' => 'two columns'));

        $Div->appendChild(new XMLElement('p', __('Review your details and start the installation when you\'re ready.'), array('class' => 'column')));


        $Submit = new XMLElement('div', null, array('class' => 'column actions submit'));
        $Submit->appendChild(Widget::Input('lang', Lang::get(), 'hidden'));

        $Submit->appendChild(Widget::Input('action[install]', __('Install Symphony'), 'submit'));

        $Div->appendChild($Submit);

        $fieldset->appendChild($Div);
        $this->Form->appendChild($fieldset);

        // Set the header status `400` only if there are errors.
        // Avoids browsers misinterpreting the page as failed load.
        // by tiloschroeder
        // if (isset($this->_params['errors'])) {
        if (!empty($this->_params['errors'])) {
            $this->setHttpStatus(Page::HTTP_STATUS_BAD_REQUEST);
        }
    }

    private function __appendError(array $codes, XMLElement &$element, $message = null, $id = null)
    {
        if (is_null($message)) {
            // $message =  __('The following errors have been reported:');
            $message = '';
        }

        foreach ($codes as $i => $c) {
            if (!isset($this->_params['errors'][$c])) {
                unset($codes[$i]);
            }
        }

        if (!empty($codes)) {
/*
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
*/

            foreach ($codes as $c) {
                $div = new XMLElement('div', null, array('class' => 'message error-message'));
                if (isset($id)) {
                    $div->setAttribute('id', $id);
                }
                $p = new XMLElement('p', $this->_params['errors'][$c]['msg'], array('class' => 'message-heading'));
                $div->appendChild($p);

                $p = new XMLElement('p', $this->_params['errors'][$c]['details'], array('class' => 'message-details'));
                $div->appendChild($p);
                $element->appendChild($div);
            }
        }
    }

    private function __appendMessage(array $codes, XMLElement &$element, $message = null)
    {
        if (!empty($codes)) {
            foreach ($codes as $c) {
                $div = new XMLElement('div', null, array('class' => 'message error-message'));
                $h3 = new XMLElement('h3', $this->_params['errors'][$c]['msg'], array('class' => 'message-heading'));
                $div->appendChild($h3);

                $p = new XMLElement('p', $this->_params['errors'][$c]['details'], array('class' => 'message-details'));
                $div->appendChild($p);
                $element->appendChild($div);
            }
        }
    }

}
