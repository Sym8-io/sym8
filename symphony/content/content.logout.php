<?php
/**
 * @package content
 */
/**
 * The default Logout page will redirect the user
 * to the Homepage of `URL`
 */
class contentLogout extends HTMLPage
{
    public function __construct()
    {
        // Redirect to the login page if user is not logged in
        $author = Symphony::Author();
        if ($author === null) {
            redirect(SYMPHONY_URL . '/login/');
        }

        parent::__construct();

        $this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');

        $this->Html->setElementStyle('html');
        $this->Html->setDTD('<!DOCTYPE html>');
        $this->Html->setAttribute('lang', Lang::get());
        $this->Html->setAttribute('dir', 'ltr');
        $this->addElementToHead(new XMLElement('meta', null, array('charset' => 'UTF-8')), 0);
        $this->addElementToHead(new XMLElement('meta', null, array('http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge')), 1);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1')), 2);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'color-scheme', 'content' => 'dark light')), 3);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'robots', 'content' => 'noindex')), 4);

        parent::addStylesheetToHead(ASSETS_URL . '/css/pico.min.css', 'screen', null, false, true);
        parent::addStylesheetToHead(ASSETS_URL . '/css/pico-login.css', 'screen', null, false, true);
        parent::addStylesheetToHead(ASSETS_URL . '/css/pico-messages.css', 'screen', null, false, true);

        $this->Body->setAttribute('id', 'loggedout');

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

    public function build()
    {
        $this->view();
    }

    public function view()
    {
        Administration::instance()->logout();

        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Logged out'), Symphony::Configuration()->get('sitename', 'general'))));

        $main = new XMLElement('main', null, array('class' => 'container loggedout'));

        $siteName = new XMLElement('h1', __('Symphony'));

        $div = new XMLElement('div', null, array('class' => 'frame'));

        $divInner = new XMLElement('div', null, array('class' => 'inner'));
        $divInner->appendChild(new XMLElement('h2', __('Goodbye')));

        $svg = new XMLElement('svg', null, array('xmlns' => 'http://www.w3.org/2000/svg', 'height' => '24', 'width' => '24', 'viewBox' => '0 0 24 24', 'focusable' => 'false', 'class' => 'icons waving-hand', 'role' => 'presentation'));
        $path = new XMLElement('path');
        $path->setAttribute('d', 'm10.639 11.456 7.7-7.7q.327-.327.763-.327t.762.326q.326.327.326.762 0 .436-.327.762l-7.673 7.7zm2.694 2.694 6.911-6.939q.327-.326.776-.326t.776.326q.326.327.326.776t-.326.775l-6.912 6.912zM4.68 19.32q-2.476-2.476-2.476-5.96 0-3.482 2.476-5.958l3.265-3.266L9.55 5.742q.191.19.327.394t.272.422l4.027-4.054q.327-.327.776-.327t.775.327.327.775-.327.776L11.02 8.762l-2.313 2.286.517.517q1.252 1.252 1.197 2.993-.054 1.742-1.333 3.02l-1.551-1.523q.626-.626.694-1.483t-.558-1.483l-1.279-1.252q-.326-.327-.326-.776t.326-.775l1.551-1.524q.327-.326.327-.775t-.327-.776L6.204 8.953q-1.85 1.85-1.85 4.421t1.85 4.422 4.435 1.85 4.435-1.85l6.504-6.53q.326-.327.775-.327.45 0 .776.327t.326.775-.326.776l-6.53 6.503q-2.477 2.476-5.96 2.476T4.68 19.32M17.442 24v-2.204q1.796 0 3.074-1.28 1.28-1.278 1.28-3.074h2.203q0 2.721-1.918 4.64T17.441 24M0 6.558q0-2.721 1.918-4.64T6.558 0v2.204q-1.796 0-3.075 1.279t-1.28 3.075z');
        $svg->appendChild($path);

        $status = new XMLElement('p', __('You have been successfully logged out.'), array('class' => 'message success'));

        $divInner->appendChild($svg);
        $divInner->appendChild($status);

        $div->appendChild($divInner);

        // Actions
        $actions = new XMLElement('div', null, array('class' => 'actions'));
        $actions->appendChild(
            new XMLElement('a', __('Login again'), array('href' => SYMPHONY_URL . '/login/', 'role' => 'button', 'class' => 'primary login-link'))
        );
        $div->appendChild($actions);

        // Back to frontpage link
        $p = new XMLElement('p', null, array('class' => 'back-to-website'));
        $backLink = new XMLElement('a', __("back to ") . Symphony::Configuration()->get('sitename', 'general'));
        $backLink->setAttribute('href', URL);
        $backLink->setAttribute('class', 'secondary back-link');
        $p->appendChild($backLink);

        $main->appendChild($siteName);
        $main->appendChild($div);
        $main->appendChild($p);

        $this->Body->appendChild($main);
    }
}
