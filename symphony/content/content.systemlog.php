<?php
/**
 * @package content
 */
/**
 * Displays the contents of the Symphony `ACTIVITY_LOG`
 * log to any user who is logged in. If a user is not logged
 * in, or the log file is unreadable, they will be directed
 * to a 404 page
 */
require_once(TOOLKIT . '/class.administrationpage.php');

class contentSystemLog extends AdministrationPage
{
    public function build(array $context = array())
    {
        parent::build();
        // $this->Context->appendChild(new XMLElement('h2', __('Symphony Log')));
    }

    public function view()
    {
        $this->setPageType('form');
        $this->setTitle(__('Symphony Log'));
        $this->appendSubheading(__('Symphony Log'));

        $logPath = ACTIVITY_LOG;
        $logContents = 'Log file not found.';

        if (file_exists($logPath)) {
            $logContents = file_get_contents($logPath);
            $logContents = htmlentities($logContents, ENT_QUOTES, 'UTF-8');
        }

        $div = new XMLElement('div', null, array('class' => 'main-log'));
        $pre = new XMLElement('pre', $logContents);
        $div->appendChild($pre);

        $this->Form->appendChild($div);
    }

}

#class contentSystemLog
#{
#    public function build()
#    {
#        if (!is_file(ACTIVITY_LOG) || !$log = @file_get_contents(ACTIVITY_LOG)) {
#            Administration::instance()->errorPageNotFound();
#        }

#        header('Content-Type: text/plain');

#        print $log;
#        exit;
#    }
#}
