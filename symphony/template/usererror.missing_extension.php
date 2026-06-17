<?php

$match = "";
$rename_failed = false;

// Fetch extensions
if (@file_exists(EXTENSIONS)) {
    $extensions = new DirectoryIterator(EXTENSIONS);
    // Look for folders that could be the same as the desired extension
    foreach ($extensions as $extension) {
        if ($extension->isDot() || $extension->isFile()) {
            continue;
        }

        // See if we can find an extension in any of the folders that has the id we are looking for in `extension.meta.xml`
        if (@file_exists($extension->getPathname() . "/extension.meta.xml")) {
            $xsl = @file_get_contents($extension->getPathname() . "/extension.meta.xml");
            $xsl = @new SimpleXMLElement($xsl);
            if (!$xsl) {
                continue;
            }
            $xsl->registerXPathNamespace("ext", "http://getsymphony.com/schemas/extension/1.0");
            $result = $xsl->xpath("//ext:extension[@id = '" . $e->getAdditional()->name . "']");

            if (!empty($result)) {
                $match = $extension->getFilename();
                break;
            }
        }
    }
}

// The extension cannot be found, show an error message and
// let the user remove or rename the extension folder.
if (isset($_POST['extension-missing'])) {
    $redirect = false;
    if (isset($_POST['action']['delete'])) {
        Symphony::ExtensionManager()->cleanupDatabase();
        $redirect = true;
    } elseif (isset($_POST['action']['rename']) && $match != "") {
        $path = ExtensionManager::__getDriverPath($match);

        if (!@rename(EXTENSIONS . '/' . $match, EXTENSIONS . '/' . $e->getAdditional()->name)) {
            $rename_failed = true;
        } else {
            $redirect = true;
        }
    }
    if ($redirect) {
        redirect(SYMPHONY_URL . '/system/extensions/');
    }
}

$author = Symphony::Author();

$Page = new HTMLPage();

$Page->Html->setElementStyle('html');

$Page->Html->setDTD('<!DOCTYPE html>');
$Page->Html->setAttribute('lang', 'en');
$Page->Html->setAttribute('dir', 'ltr');
$Page->Html->setAttribute('data-type', 'extension');
$Page->addElementToHead(new XMLElement('meta', null, array('charset' => 'UTF-8')), 0);
$Page->addElementToHead(new XMLElement('meta', null, array('name' => 'robots', 'content' => 'noindex')), 1);
$Page->addElementToHead(new XMLElement('meta', null, array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1')), 2);
$Page->addElementToHead(new XMLElement('meta', null, array('name' => 'color-scheme', 'content' => 'dark light')), 3);
$Page->addStylesheetToHead(ASSETS_URL . '/css/pico.min.css', 'screen', null, false, true);
$Page->addStylesheetToHead(ASSETS_URL . '/css/pico-error.css', 'screen', null, false, true);
$Page->addStylesheetToHead(ASSETS_URL . '/css/pico-messages.css', 'screen', null, false, true);

$Page->setHttpStatus($e->getHttpStatusCode());
$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
$Page->addHeaderToPage('Symphony-Error-Type', 'missing-extension');

$Page->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $e->getHeading())));
$Page->Body->setAttribute('id', 'error');

$main = new XMLElement('main', null, array('class' => 'container errorpage'));
$main->appendChild(new XMLElement('h1', __('Symphony')));

$div = new XMLElement('div', null, array('class' => 'frame'));
$divInner = new XMLElement('div', null, array('class' => 'inner'));
$divInner->appendChild(new XMLElement('h2', $e->getHeading()));
if ($author === null) {
    $divInner->appendChild(
        new XMLElement('p', __('A required Symphony extension could not be loaded.<br>Please contact the site administrator.'), array('class' => 'message invalid'))
    );
} elseif ($author !== null) {
    $divInner->appendChild(
        new XMLElement('p', trim($e->getMessage()), array('class' => 'message invalid'))
    );
}

// Build the form, what it can do is yet to be determined
$form = new XMLElement('form', null, array('action' => SYMPHONY_URL. '/system/extensions/', 'method' => 'post'));
$form->appendChild(
    Widget::Input('extension-missing', 'yes', 'hidden')
);
$actions = new XMLElement('div');
$actions->setAttribute('class', 'actions');

$actions->appendChild(Widget::Input('action[delete]', __('Uninstall extension'), 'submit', array(
    'accesskey' => 'd',
    'class' => 'button delete',
    'style' => 'margin-left: 0;',
    'title' => __('Uninstall this extension'),
)));

$form->appendChild($actions);

if ($author !== null) {
    // if the renamed failed
    if ($match != "" && $rename_failed) {
        $divInner->appendChild(
            new XMLElement('p', __('Sorry, but Symphony was unable to rename the folder. You can try renaming %s to %s yourself, or you can uninstall the extension to continue.', array(
                '<code>extensions/' . General::sanitize($match) . '</code>',
                '<code>extensions/' . General::sanitize($e->getAdditional()->name) . '</code>'
            )), array('class' => 'message invalid'))
        );
    }
    // If we've found a similar folder
    elseif ($match != "") {
        $divInner->appendChild(
            new XMLElement('p', __('Often the cause of this error is a misnamed extension folder. You can try renaming %s to %s, or you can uninstall the extension to continue.', array(
                '<code>extensions/' . $match . '</code>',
                '<code>extensions/' . $e->getAdditional()->name . '</code>'
            )), array('class' => 'message info'))
        );

        $button = new XMLElement('button', __('Rename folder'));
        $button->setAttributeArray(array(
            'name' => 'action[rename]',
            'class' => 'button',
            'type' => 'submit',
            'accesskey' => 's'
        ));
        $actions->appendChild($button);
    } else {
        if ($author->isDeveloper()) {
            $divInner->appendChild(
                new XMLElement('p', __('You can try uninstalling the extension to continue, or you might want to ask on the forums'), array('class' => 'message info'))
            );
        } else {
            $divInner->appendChild(
                new XMLElement('p', __('Please contact the site administrator.'), array('class' => 'message info'))
            );
        }
    }
}

// Add XSRF token to form's in the backend
if (Symphony::Engine()->isXSRFEnabled()) {
    #$form->prependChild(XSRF::formToken());
}

// Intentionally no automatic recovery actions are provided here.
// Renaming extension directories or uninstalling missing extensions via the
// web interface can cause unexpected side effects. The administrator
// should restore the extension files or uninstall the extension manually
// after checking its dependencies.
if ($author !== null) {
    if ($author->isDeveloper()) {
        #$divInner->appendChild($form);
    }
}

$div->appendChild($divInner);

$main->appendChild($div);

$Page->Body->appendChild($main);

$output = $Page->generate();
echo $output;

exit;
