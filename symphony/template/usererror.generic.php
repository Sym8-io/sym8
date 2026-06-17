<?php

$Page = new HTMLPage();

$Page->Html->setElementStyle('html');

$Page->Html->setDTD('<!DOCTYPE html>');
$Page->Html->setAttribute('lang', 'en');
$Page->Html->setAttribute('dir', 'ltr');
$Page->Html->setAttribute('data-type', 'generic');
// Enable for testing
// $Page->Html->setAttribute('data-theme', 'light');
$Page->addElementToHead(new XMLElement('meta', null, array('charset' => 'UTF-8')), 0);
$Page->addElementToHead(new XMLElement('meta', null, array('name' => 'robots', 'content' => 'noindex')), 1);
$Page->addElementToHead(new XMLElement('meta', null, array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1')), 2);
$Page->addElementToHead(new XMLElement('meta', null, array('name' => 'color-scheme', 'content' => 'dark light')), 3);
$Page->addStylesheetToHead(ASSETS_URL . '/css/pico.min.css', 'screen', null, false, true);
$Page->addStylesheetToHead(ASSETS_URL . '/css/pico-error.css', 'screen', null, false, true);
$Page->addStylesheetToHead(ASSETS_URL . '/css/pico-messages.css', 'screen', null, false, true);

$Page->setHttpStatus($e->getHttpStatusCode());
$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
$Page->addHeaderToPage('Symphony-Error-Type', 'generic');

if (isset($e->getAdditional()->header)) {
    $Page->addHeaderToPage($e->getAdditional()->header);
}

$Page->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $e->getHeading())));
$Page->Body->setAttribute('id', 'error');

$main = new XMLElement('main', null, array('class' => 'container errorpage'));
$main->appendChild(new XMLElement('h1', __('Symphony')));
$div = new XMLElement('div', null, array('class' => 'frame'));
$divInner = new XMLElement('div', null, array('class' => 'inner'));
$divInner->appendChild(new XMLElement('h2', $e->getHeading()));
$divInner->appendChild(
    ($e->getMessageObject() instanceof XMLElement ? $e->getMessageObject() : new XMLElement('p', trim($e->getMessage()), array('class' => 'message invalid')))
);
$div->appendChild($divInner);

$main->appendChild($div);

$Page->Body->appendChild($main);

$output = $Page->generate();
echo $output;

exit;
