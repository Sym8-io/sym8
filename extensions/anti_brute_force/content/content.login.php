<?php

if (!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

require_once CONTENT . '/content.login.php';
require_once EXTENSIONS . '/anti_brute_force/lib/class.ABF.php';

/*
License: MIT
*/

/**
 *
 * N.B. : Page is named login in order for the Administration Class to
 * pretend in the login page via <code>$this->_context['driver']</code>.
 * Should certainly be named something else
 * @author nicolasbrassard
 *
 */
class contentExtensionAnti_brute_forceLogin extends contentLogin
{
    private $_email_sent = null;

    /**
     * Testing the Pico CSS login style to ensure backward compatibility
     * with the old Symphony style in a minimally invasive way.
     */
    private function oldSymphony()
    {
        if (!file_exists(SYMPHONY . '/assets/css/pico-login.css')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * Overrides the view method
     */
    public function view()
    {
        // if this is the unban request
        if (isset($this->_context) && is_array($this->_context) && count($this->_context) > 0) {
            // check if we have a hash present
            $hash = $this->_context[0];
            if (strlen($hash) == 36) {
                // Sanatize user inputed values... ALWAYS
                $hash = General::sanitize($hash);
                $this->__unban($hash);
            }

            // redirect not matter what
            // evil users won't be able to detect anything from the response
            // they *should* still be blocked since guessing a hash is
            // practically infeasible
            redirect(SYMPHONY_URL . '/login/');
            die();

        } else {

            // not banned ? do not show this page!
            if (!ABF::instance()->isCurrentlyBanned()) {
                redirect(SYMPHONY_URL);
                die();
            }

            $this->setTitle(sprintf('%1$s &ndash; %2$s', __('Unban via email'), __('Symphony')));

            $main = new XMLElement('main', null);
            if ($this->oldSymphony() === true) {
                $main->setAttribute('class', 'container frame');
            } else {
                $main->setAttribute('class', 'container login');
            }

            $this->Form = Widget::Form('/symphony/extension/anti_brute_force/login/', 'post', '');
            if ($this->oldSymphony() === false) {
                $this->Form->setAttribute('class', 'frame');
            }

            // H1 should be appear above the form
            $siteName = new XMLElement('h1', __('Symphony'));
            $main->appendChild($siteName);

            $this->__buildFormContent();

            $p = new XMLElement('p', null, array('class' => 'back-to-website'));
            $backLink = new XMLElement('a', __("back to ") . Symphony::Configuration()->get('sitename', 'general'));
            $backLink->setAttribute('href', URL);
            $backLink->setAttribute('class', 'secondary back-link');
            $p->appendChild($backLink);

            $main->appendChild($this->Form);
            $main->appendChild($p);

            $this->Body->appendChild($main);
        }
    }

    private function __buildFormContent()
    {
        $divInner = new XMLElement('div', null, array('class' => 'inner'));
        $divInner->appendChild(new XMLElement('h2', __('Unban via email')));

        $divField = new XMLElement('div', null, array('class' => 'form-field'));

        // email was not send
        // or first time here (email_sent == NULL)
        if ($this->_email_sent !== true) {

            $email = $_POST['email'] ?? null;

            $divInner->appendChild(new XMLElement('p', __('Enter your email address to be sent a remote unban link with further instructions.'), array('class' => 'message info')));

            $label = Widget::Label(__('Email Address'));
            $label->setAttribute('for', 'userlogin');
            // Do not set the "autofocus" attribute directly.
            // On touch devices this can cause the virtual keyboard
            // to open automatically, resulting in a disruptive UX.
            //
            // Focus handling is applied via Javascript depending on
            // the device input type (touch vs. non-touch).
            $input = Widget::Input('email', General::sanitize($email), 'email', array('id' => 'userlogin', 'data-autofocus' => 'true', 'required' => 'required', 'autocapitalize' => 'off', 'autocomplete' => 'email'));

        }

        if (isset($this->_email_error) && $this->_email_error) {
            $alert = new XMLElement('p', __('This Symphony instance has not been set up for emailing, %s', array('<code>' . General::sanitize($this->_email_error) . '</code>')));
            $alert->setAttribute('role', 'alert');
            if ($this->oldSymphony() === true) {
                $alert->setAttribute('class', 'invalid');
            } else {
                $alert->setAttribute('class', 'message invalid');
            }
            $input->setAttribute('aria-invalid', 'false');
            $divInner->appendChild($alert);
        } elseif (isset($this->_email_sent)) {

            if ($this->_email_sent === false) {

                $errorId = 'invalid-email';
                $errorMsg = new XMLElement('small', __('There was a problem locating your account. Please check that you are using the correct email address.'));
                $errorMsg->setAttribute('id', $errorId);
                $errorMsg->setAttribute('role', 'alert');
                if ($this->oldSymphony() === true) {
                    $errorMsg->setAttribute('class', 'invalid');
                }
                $input->setAttribute('aria-invalid', 'true');
                $input->setAttribute('aria-describedby', $errorId);
            } elseif ($this->_email_sent === true) {

                $div = new XMLElement('div', __('Check your inbox. ') . __('We have sent you an email. Follow the instruction in it.'), array('class' => 'message success'));
                $divInner->appendChild($div);
            }
        }

        if (!isset($this->_email_sent) || $this->_email_sent !== true) {
            $divField->appendChild($label);
            $divField->appendChild($input);
            if (isset($errorMsg)) {
                $divField->appendChild($errorMsg);
            }
        }

        $divInner->appendChild($divField);
        $this->Form->appendChild($divInner);

        if ($this->_email_sent !== true) {
            $div = new XMLElement('div', NULL, array('class' => 'actions'));
            $div->appendChild(new XMLElement('button', __('Send Email'), array('name' => 'action[send-email]', 'type' => 'submit')));
            $this->Form->appendChild($div);
        }
    }

    /**
     *
     * Overrides the action method
     */
    public function action()
    {
        // set error flag
        $this->_email_sent = false;

        if (isset($_POST['action']) && is_array($_POST['action'])) {

            foreach ($_POST['action'] as $action => $value) {

                switch ($action) {
                    case 'send-email':
                        $this->__sendEmail();
                        break;
                }
            }
        }
    }

    private function __sendEmail()
    {
        $emailUnban = ABF::instance()->getConfigVal(ABF::SETTING_AUTO_UNBAN);
        if ($emailUnban != 'on') {
            // do nothing
            $this->_email_sent = null;

            return;
        }

        $author = Symphony::Database()->fetchRow(0, "SELECT `id`, `email`, `first_name` FROM `tbl_authors` WHERE `email` = '".MySQL::cleanValue($_POST['email'])."'");
        $failure = ABF::instance()->getFailureByIp();

        $emailSettings = ABF::instance()->getEmailSettings();

        if (is_array($author) && isset($author['email']) &&
            is_array($failure) && isset($failure[0]) && isset($failure[0]->Hash)) {
            // safe run
            try {
                // use default values
                $email = Email::create();

                // if no default values are set
                if (!is_array($emailSettings) || empty($emailSettings['from_address'])) {
                    // Do not fall back to the recipient email address as sender.
                    // While this was common practice years ago, modern mail providers
                    // (Google, Yahoo, Microsoft) enforce strict SPF/DKIM/DMARC policies,
                    // making forged or mismatched sender addresses unreliable and likely
                    // to be rejected or flagged as spam.
                    $this->_email_sent = false;
                    $this->_email_error = __('Sender email address cannot be empty.');

                    return;
                }
                // use default settings, as this should help with SPF and DKIM
                else {
                    $email->setFrom($emailSettings['from_address'], $emailSettings['from_name']);
                }

                $email->setRecipients($author['email']);
                $email->setSubject(__('Unban IP link'));
                $email->setTextPlain(
                    __('Hi %s,', array($author['first_name'])) . PHP_EOL .
                    __('Please follow this link to unban your IP: ') . PHP_EOL . PHP_EOL .
                    '    ' . SYMPHONY_URL . ABF::UNBAND_LINK . $failure[0]->Hash . '/' . PHP_EOL . PHP_EOL .
                    __('If you do not remember your password, follow the "forgot password" link on the login page.') . PHP_EOL . PHP_EOL .
                    __('Best Regards,') . PHP_EOL .
                    __('The Symphony Team')
                );

                // set error flag
                $this->_email_sent = $email->send();

            } catch (Exception $e) {
                // do nothing
                $this->_email_sent = false;
                $this->_email_error = $e->getMessage();
            }
        }
    }

    private function __unban($hash)
    {
        ABF::instance()->unregisterFailure($hash);
    }
}
