<?php

$Page = new HTMLPage();

$Page->Html->setElementStyle('html');

$Page->Html->setDTD('<!DOCTYPE html>');
$Page->Html->setAttribute('lang', 'en');
$Page->Html->setAttribute('dir', 'ltr');
$Page->Html->setAttribute('data-type', 'xslt');
$Page->addElementToHead(new XMLElement('meta', null, array('charset' => 'UTF-8')), 0);
$Page->addElementToHead(new XMLElement('meta', null, array('name' => 'robots', 'content' => 'noindex')), 1);
$Page->addElementToHead(new XMLElement('meta', null, array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1')), 2);
$Page->addElementToHead(new XMLElement('meta', null, array('name' => 'color-scheme', 'content' => 'dark light')), 3);
$Page->addStylesheetToHead(ASSETS_URL . '/css/pico.min.css', 'screen', null, false, true);
$Page->addStylesheetToHead(ASSETS_URL . '/css/pico-error.css', 'screen', null, false, true);
$Page->addStylesheetToHead(ASSETS_URL . '/css/pico-messages.css', 'screen', null, false, true);

$Page->setHttpStatus($e->getHttpStatusCode());
$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
$Page->addHeaderToPage('Symphony-Error-Type', 'xslt');

$Page->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('XSLT Processing Error'))));
$Page->Body->setAttribute('id', 'error');
$Page->Body->setAttribute('class', 'template-generic');

$main = new XMLElement('main', null, array('class' => 'container errorpage'));
$main->appendChild(new XMLElement('h1', __('Symphony')));
$div = new XMLElement('div', null, array('class' => 'frame'));
$divInner = new XMLElement('div', null, array('class' => 'inner'));
$divInner->appendChild(new XMLElement('h2', __('XSLT Processing Error')));
$divInner->appendChild(new XMLElement('p', __('This page could not be rendered due to the following XSLT processing errors:'), array('class' => 'message invalid')));

$errors = $e->getAdditional()->proc->getError(true, true);

$ul = new XMLElement('ul');

foreach ($errors as $error) {
    $li = new XMLElement('li');
    $li->setValue('<code>' . htmlspecialchars($error['message'], ENT_QUOTES, 'UTF-8') . '</code>');
    $ul->appendChild($li);
}

$divInner->appendChild($ul);

$div->appendChild($divInner);

$main->appendChild($div);

$Page->Body->appendChild($main);

print $Page->generate();

exit;
