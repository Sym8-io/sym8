<?php

/**
 * @package content
 */

/**
 * The default Symphony login page that is shown to users who attempt
 * to access `SYMPHONY_URL` but are not logged in. This page has logic
 * to allow users to reset their passwords should they forget.
 */
class contentLogin extends HTMLPage
{
    public $failedLoginAttempt = false;

    public function __construct()
    {
        // Redirect logged in users to Symphony backend
        $author = Symphony::Author();
        if ($author !== null) {
            redirect(SYMPHONY_URL . '/');
        }

        parent::__construct();

        $this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');

        $this->Html->setElementStyle('html');
        $this->Html->setDTD('<!DOCTYPE html>');
        $this->Html->setAttribute('lang', Lang::get());
        $this->Html->setAttribute('dir', 'ltr');
        // Enable for testing
        // $this->Html->setAttribute('data-theme', 'light');
        $this->addElementToHead(new XMLElement('meta', null, array('charset' => 'UTF-8')), 0);
        $this->addElementToHead(new XMLElement('meta', null, array('http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge')), 1);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1')), 2);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'color-scheme', 'content' => 'dark light')), 3);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'robots', 'content' => 'noindex')), 4);

        parent::addStylesheetToHead(ASSETS_URL . '/css/pico.min.css', 'screen', null, false, true);
        parent::addStylesheetToHead(ASSETS_URL . '/css/pico-login.css', 'screen', null, false, true);
        parent::addStylesheetToHead(ASSETS_URL . '/css/pico-messages.css', 'screen', null, false, true);

        parent::addScriptToHead(ASSETS_URL . '/js/login.js', null, false, true, false);

        $this->Body->setAttribute('id', 'login');

        Symphony::Profiler()->sample('Page template created', PROFILE_LAP);
    }

    public function addScriptToHead($path, $position = null, $duplicate = true, $version = true, $module = false)
    {
        // Prevent script injection by extensions
    }

    public function addStylesheetToHead($path, $type = 'screen', $position = null, $duplicate = true, $version = true)
    {
        // Prevent stylesheet injection by extensions
    }

    public function build($context = null)
    {
        if ($context) {
            $this->_context = $context;
        }

        if (isset($_REQUEST['action'])) {
            $this->action();
        }

        $this->view();
    }

    public function view()
    {
        if (isset($this->_context[0]) && in_array(strlen($this->_context[0]), array(6, 8, 16))) {
            if (!$this->__loginFromToken($this->_context[0])) {
                if (Administration::instance()->isLoggedIn()) {
                    // Redirect to the Author's profile. RE: #1801
                    redirect(SYMPHONY_URL . '/system/authors/edit/' . Symphony::Author()->get('id') . '/reset-password/');
                }
            }
        }

        // set the page title depended from context
        if (isset($this->_context[0]) && $this->_context[0] == 'retrieve-password') {
            $this->setTitle(__('%1$s &ndash; %2$s', array(__('Password forgotten'), Symphony::Configuration()->get('sitename', 'general'))));
        } else {
            $this->setTitle(__('%1$s &ndash; %2$s', array(__('Login'), Symphony::Configuration()->get('sitename', 'general'))));
        }

        $siteName = new XMLElement('h1', __('Symphony'));

        $this->Form = Widget::Form(SYMPHONY_URL . '/login/', 'post', 'frame');

        $divInner = new XMLElement('div', null, array('class' => 'inner'));

        // Display retrieve password UI
        if (isset($this->_context[0]) && $this->_context[0] == 'retrieve-password') {
            $this->Form->setAttribute('action', SYMPHONY_URL.'/login/retrieve-password/');
            $divInner->appendChild(new XMLElement('h2', __('Password forgotten')));

            // Successful reset
            if (isset($this->_email_sent) && $this->_email_sent) {
                $divInner->appendChild(new XMLElement('p', __('Check your inbox. ') . __('An email containing a customised login link has been sent to %s. It will expire in 2 hours.', array(
                    '<code>' . $this->_email_sent_to . '</code>')
                ), array('class' => 'message success')));
                $divInner->appendChild(new XMLElement('p', Widget::Anchor(__('Login'), SYMPHONY_URL.'/login/', null)));
                $this->Form->appendChild($divInner);

                // Default, get the email address for reset
            } else {
                $divInner->appendChild(new XMLElement('p', __('Enter your email address or username to be sent further instructions for logging in.'), array('class' => 'message info')));

                $div = new XMLElement('div', null, array('class' => 'form-field'));
                $label = Widget::Label(__('Email Address or Username'));
                $label->setAttribute('for', 'userlogin');
                $_POST['email'] = $_POST['email'] ?? null;
                // Do not set the "autofocus" attribute directly.
                // On touch devices this can cause the virtual keyboard
                // to open automatically, resulting in a disruptive UX.
                //
                // Focus handling is applied via Javascript depending on
                // the device input type (touch vs. non-touch).
                $input = Widget::Input('email', General::sanitize($_POST['email']), 'text', array('data-autofocus' => 'true'));
                $input->setAttribute('id', 'userlogin');
                $input->setAttribute('required', 'required');
                $input->setAttribute('autocapitalize', 'off');
                $input->setAttribute('autocomplete', 'username');

                if (isset($this->_email_sent) && !$this->_email_sent) {
                    $errorId = 'error-account';
                    $alert = new XMLElement('small', __('Unfortunately no account was found using this information.'));
                    $alert->setAttribute('id', $errorId);
                    $alert->setAttribute('role', 'alert');
                    $input->setAttribute('aria-invalid', 'true');
                    $input->setAttribute('aria-describedby', $errorId);
                    $input->appendChild($alert);
                } else {
                    // Email exception
                    if (isset($this->_email_error) && $this->_email_error) {
                        $alert = new XMlElement('p', __('This Symphony instance has not been set up for emailing, %s', array('<code>' . General::sanitize($this->_email_error) . '</code>')));
                        $alert->setAttribute('role', 'alert');
                        $alert->setAttribute('class', 'message invalid');
                        $input->setAttribute('aria-invalid', 'false');
                        $div->appendChild($alert);
                    }
                }

                $div->appendChild($label);
                $div->appendChild($input);

                $divInner->appendChild($div);

                $this->Form->appendChild($divInner);

                $div = new XMLElement('div', null, array('class' => 'actions'));
                $div->appendChild(
                    new XMLElement('button', __('Send Email'), array('name' => 'action[reset]', 'type' => 'submit'))
                );
                $p = new XMLElement('p');
                $p->appendChild(
                    Widget::Anchor(__('Cancel'), SYMPHONY_URL.'/login/', null, 'action-link')
                );
                $div->appendChild($p);
                $this->Form->appendChild($div);
            }

            // Normal login
        } else {
            $divInner->appendChild(new XMLElement('h2', __('Login')));

            // Display error message
            if ($this->failedLoginAttempt) {
                $p = new XMLElement('p', __('The login details provided are incorrect.'));
                $p->setAttribute('class', 'message invalid');
                $p->setAttribute('role', 'alert');
                $divInner->appendChild($p);
            }

            // Username
            $div = new XMLElement('div', null, array('class' => 'form-field'));
            $label = Widget::Label(__('Username'));
            $label->setAttribute('for', 'username');

            $username = Widget::Input('username', isset($_POST['username']) ? General::sanitize($_POST['username']) : null);
            $username->setAttribute('id', 'username');
            $username->setAttribute('required', 'required');
            $username->setAttribute('autocapitalize', 'off');
            $username->setAttribute('autocomplete', 'username');

            if (!$this->failedLoginAttempt) {
                $username->setAttribute('data-autofocus', '');
            }

            if ($this->failedLoginAttempt) {
                $username->setAttribute('aria-invalid', 'true');
            }

            $div->appendChild($label);
            $div->appendChild($username);

            if (isset($_POST['action'], $_POST['action']['login']) && empty($_POST['username'])) {
                $errorId = 'empty-username';
                // Do not set the "autofocus" attribute directly.
                // On touch devices this can cause the virtual keyboard
                // to open automatically, resulting in a disruptive UX.
                //
                // Focus handling is applied via Javascript depending on
                // the device input type (touch vs. non-touch).
                $username->setAttribute('data-autofocus', '');
                $username->setAttribute('aria-describedby', $errorId);
                $errorMsg = new XMLElement('small', __('No username was entered.'));
                $errorMsg->setAttribute('id', $errorId);
                $div->appendChild($errorMsg);
            }

            $divInner->appendChild($div);

            // Password
            $div = new XMLElement('div', null, array('class' => 'form-field'));
            $label = Widget::Label(__('Password'));
            $label->setAttribute('for', 'password');

            $div1 = new XMLElement('div', null, array('class' => 'input-group'));
            $password = Widget::Input('password', null, 'password');
            $password->setAttribute('id', 'password');
            $password->setAttribute('required', 'required');
            $password->setAttribute('autocomplete', 'current-password');
            $password->setAttribute('spellcheck', 'false');
            $div1->appendChild($password);

            $btn = new XMLElement('button');
            $btn->setAttribute('aria-label', __('Show password'));
            $btn->setAttribute('aria-pressed', 'false');
            $btn->setAttribute('aria-controls', 'password');
            $btn->setAttribute('data-label-show', __('Show password'));
            $btn->setAttribute('data-label-hide', __('Hide password'));
            $btn->setAttribute('type', 'button');
            $btn->setAttribute('class', 'show-hide-password secondary outline');
            $btn->setValue('<span class="icon-show">
                <!-- visibility -->
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 24 24" focusable="false"><path d="M15.188 14.688q1.313-1.312 1.313-3.187t-1.313-3.188-3.187-1.312-3.188 1.312-1.312 3.188 1.312 3.187 3.188 1.313 3.187-1.313m-5.1-1.275q-.787-.787-.787-1.912t.787-1.913 1.913-.787 1.912.787.788 1.913-.788 1.912-1.912.788-1.913-.788m-4.737 3.55q-3-2.037-4.35-5.462 1.35-3.425 4.35-5.463T12 4.001t6.65 2.037T23 11.501q-1.35 3.425-4.35 5.462T12 19.001t-6.65-2.038m11.837-1.45q2.363-1.487 3.613-4.012-1.25-2.525-3.613-4.013T12 6.001 6.812 7.488 3.2 11.501q1.25 2.525 3.612 4.012T12 17.001t5.187-1.488"/></svg>
            </span>
            <span class="icon-hide" hidden>
                <!-- visibility_off -->
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" viewBox="0 0 24 24" focusable="false"><path d="m16.1 13.3-1.45-1.45q.225-1.175-.675-2.2t-2.325-.8L10.2 7.4q.425-.2.863-.3T12 7q1.875 0 3.188 1.313T16.5 11.5q0 .5-.1.938t-.3.862m3.2 3.15-1.45-1.4q.95-.725 1.688-1.588T20.8 11.5q-1.25-2.525-3.587-4.013T12 6q-.725 0-1.425.1T9.2 6.4L7.65 4.85q1.025-.425 2.1-.638T12 4q3.775 0 6.725 2.087T23 11.5q-.575 1.475-1.512 2.737T19.3 16.45m.5 6.15-4.2-4.15q-.875.275-1.762.413T12 19q-3.775 0-6.725-2.087T1 11.5q.525-1.325 1.325-2.462T4.15 7L1.4 4.2l1.4-1.4 18.4 18.4zM5.55 8.4q-.725.65-1.325 1.425T3.2 11.5q1.25 2.525 3.588 4.013T12 17q.5 0 .975-.062t.975-.138l-.9-.95q-.275.075-.525.113T12 16q-1.875 0-3.188-1.312T7.5 11.5q0-.275.037-.525t.113-.525z"/></svg>
            </span>');
            $div1->appendChild($btn);

            $div->appendChild($label);
            $div->appendChild($div1);

            if (isset($_POST['action'], $_POST['action']['login']) && empty($_POST['password'])) {
                $errorId = 'empty-password';
                $password->setAttribute('aria-invalid', 'true');
                $password->setAttribute('aria-describedby', $errorId);
                if (!empty($_POST['username'])) {
                    $password->setAttribute('autofocus', 'autofocus');
                }
                $errorMsg = new XMLElement('small', __('No password was entered.'));
                $errorMsg->setAttribute('id', $errorId);
                $div->appendChild($errorMsg);
            } elseif ($this->failedLoginAttempt) {
                $password->setAttribute('aria-invalid', 'true');
            }

            $divInner->appendChild($div);

            $this->Form->appendChild($divInner);

            // Actions
            $div = new XMLElement('div', null, array('class' => 'actions'));
            $div->appendChild(
                new XMLElement('button', __('Login'), array('name' => 'action[login]', 'type' => 'submit'))
            );
            $p = new XMLElement('p');
            $p->appendChild(
                Widget::Anchor(__('Retrieve password?'), SYMPHONY_URL.'/login/retrieve-password/', null, 'action-link')
            );
            $div->appendChild($p);
            $this->Form->appendChild($div);

            if (isset($this->_context['redirect'])) {
                $this->Form->appendChild(
                    Widget::Input('redirect', SYMPHONY_URL . General::sanitize($this->_context['redirect']), 'hidden')
                );
            }
        }

        $p = new XMLElement('p', null, array('class' => 'back-to-website'));
        $backLink = new XMLElement('a', __("back to ") . Symphony::Configuration()->get('sitename', 'general'));
        $backLink->setAttribute('href', URL);
        $backLink->setAttribute('class', 'secondary back-link');
        $p->appendChild($backLink);

        $main = new XMLElement('main');
        if (isset($this->_context[0]) && $this->_context[0] == 'retrieve-password') {
            $main->setAttribute('class', 'container password-lost');
        } else {
            $main->setAttribute('class', 'container login');
        }
        $main->appendChild($siteName);
        $main->appendChild($this->Form);
        $main->appendChild($p);
        $this->Body->appendChild($main);
    }

    public function action()
    {
        if (isset($_POST['action'])) {
            $actionParts = array_keys($_POST['action']);
            $action = end($actionParts);

            // Login Attempted
            if ($action == 'login') {

                /**
                 * Check if the Anti Brute Force extension is installed
                 * and block banned or blacklisted IP addresses before
                 * processing the login request.
                */
                $statusABF = ExtensionManager::listInstalledHandles();
                if (
                    is_array($statusABF)
                    && in_array('anti_brute_force', $statusABF, true)
                ) {
                    require_once EXTENSIONS . '/anti_brute_force/lib/class.ABF.php';

                    ABF::instance()->doBanCheck();
                }

                if (empty($_POST['username']) || empty($_POST['password']) || !Administration::instance()->login($_POST['username'], $_POST['password'])) {
                    /**
                     * A failed login attempt into the Symphony backend
                     *
                     * @delegate AuthorLoginFailure
                     * @since Symphony 2.2
                     * @param string $context
                     * '/login/'
                     * @param string $username
                     *  The username of the Author who attempted to login.
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorLoginFailure', '/login/', array('username' => Symphony::Database()->cleanValue($_POST['username'])));
                    $this->failedLoginAttempt = true;
                } else {
                    /**
                     * A successful login attempt into the Symphony backend
                     *
                     * @delegate AuthorLoginSuccess
                     * @since Symphony 2.2
                     * @param string $context
                     * '/login/'
                     * @param string $username
                     *  The username of the Author who logged in.
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorLoginSuccess', '/login/', array('username' => Symphony::Database()->cleanValue($_POST['username'])));

                    isset($_POST['redirect']) ? redirect($_POST['redirect']) : redirect(SYMPHONY_URL . '/');
                }

                // Reset of password requested
            } elseif ($action == 'reset') {
                $author = Symphony::Database()->fetchRow(0, sprintf("
                        SELECT `id`, `email`, `first_name`
                        FROM `tbl_authors`
                        WHERE `email` = '%1\$s' OR `username` = '%1\$s'
                    ", Symphony::Database()->cleanValue($_POST['email'])
                ));

                if (!empty($author)) {
                    // Delete all expired tokens
                    Symphony::Database()->delete('tbl_forgotpass', sprintf("
                        `expiry` < '%s'", DateTimeObj::getGMT('c')
                    ));

                    // Attempt to retrieve the token that is not expired for this Author ID,
                    // otherwise generate one.
                    if (!$token = Symphony::Database()->fetchVar('token', 0, sprintf("
                            SELECT `token`
                            FROM `tbl_forgotpass`
                            WHERE `expiry` > '%s' AND `author_id` = %d
                        ",
                        DateTimeObj::getGMT('c'),
                        $author['id']
                    ))) {
                        // More secure password token generation
                        if (function_exists('openssl_random_pseudo_bytes')) {
                            $seed = openssl_random_pseudo_bytes(16);
                        } else {
                            $seed = mt_rand();
                        }

                        $token = substr(SHA1::hash($seed), 0, 16);

                        Symphony::Database()->insert(array(
                            'author_id' => $author['id'],
                            'token' => $token,
                            'expiry' => DateTimeObj::getGMT('c', time() + (120 * 60))
                        ), 'tbl_forgotpass');
                    }

                    try {
                        $email = Email::create();

                        $email->recipients = $author['email'];
                        $email->subject = __('New Symphony Account Password');
                        $email->text_plain = __('Hi %s,', array($author['first_name'])) . PHP_EOL .
                                __('A new password has been requested for your account. Login using the following link, and change your password via the Authors area:') . PHP_EOL .
                                PHP_EOL . '    ' . SYMPHONY_URL . "/login/{$token}/" . PHP_EOL . PHP_EOL .
                                __('It will expire in 2 hours. If you did not ask for a new password, please disregard this email.') . PHP_EOL . PHP_EOL .
                                __('Best Regards,') . PHP_EOL .
                                __('The Symphony Team');

                        $email->send();
                        $this->_email_sent = true;
                        $this->_email_sent_to = $author['email']; // Set this so we can display a customised message
                    } catch (Exception $e) {
                        $this->_email_error = General::unwrapCDATA($e->getMessage());
                        Symphony::Log()->pushExceptionToLog($e, true);
                    }

                    /**
                     * When a password reset has occurred and after the Password
                     * Reset email has been sent.
                     *
                     * @delegate AuthorPostPasswordResetSuccess
                     * @since Symphony 2.2
                     * @param string $context
                     * '/login/'
                     * @param integer $author_id
                     *  The ID of the Author who requested the password reset
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordResetSuccess', '/login/', array('author_id' => $author['id']));
                } else {

                    /**
                     * When a password reset has been attempted, but Symphony doesn't
                     * recognise the credentials the user has given.
                     *
                     * @delegate AuthorPostPasswordResetFailure
                     * @since Symphony 2.2
                     * @param string $context
                     * '/login/'
                     * @param string $email
                     *  The sanitised Email of the Author who tried to request the password reset
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordResetFailure', '/login/', array('email' => Symphony::Database()->cleanValue($_POST['email'])));

                    $this->_email_sent = false;
                }
            }
        }
    }

    public function __loginFromToken($token)
    {
        // If token is invalid, return to login page
        if (!Administration::instance()->loginFromToken($token)) {
            return false;
        }

        // If token is valid and is an 8 char shortcut
        if (!in_array(strlen($token), array(6, 16))) {
            redirect(SYMPHONY_URL . '/'); // Regular token-based login
        }

        return false;
    }
}
