<?php
/**
 * Copyright: Deux Huit Huit 2016
 * LICENCE: MIT https://deuxhuithuit.mit-license.org;
 */

if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

require_once TOOLKIT . '/class.datasource.php';
require_once TOOLKIT . '/class.entrymanager.php';
require_once FACE . '/interface.datasource.php';

class datasourceSri extends DataSource
{
    public $dsFilesPath;
    public $dsCache = true;
    private $dsCacheProvider;
    public $dsCacheNamespace = 'sri';
    public $dsCacheTTL = 43200; // 60 * 24 * 30;

    public function __construct()
    {
        $this->dsFilesPath = MANIFEST . '/sri.xml';
        $this->dsCacheProvider = new Cacheable(Symphony::Database());
        if (defined('HTTP_HOST')) {
            $this->dsCacheNamespace .= '-' . HTTP_HOST;
        }
    }

    /**
     * About this data source
     */
    public function about()
    {
        return array(
            'name' => __('SRI'),
                     'author' => array(
                         'name' => 'Sym8',
                         'website' => 'https://sym8.io/'
                     ),
                     'version' => '1.2.0',
                     'release-date' => '2026-03-31'
        );
    }

    /**
     * Disallow data source parsing
     */
    public function allowEditorToParse()
    {
        return false;
    }

    /**
     * This function generates a list of month and weekday names for each language provided.
     */
    public function execute(array &$param_pool=NULL)
    {
        $result = new XMLElement('sri');
        try {
            $files = $this->getFiles();
            foreach ($files as $file) {
                $xmlfile = new XMLElement('file');
                $cache = $this->dsCache ? 'hit' : 'disabled';
                $filepath = DOCROOT . '/' . $file['file'];
                $integrity = $this->getCachedIntegrity($file, $filepath);
                if (!$integrity) {
                    $cache = $this->dsCache ? 'miss' : 'disabled';

                    $integrity = $this->computeIntegrity($file, $filepath);

                    if (!$integrity) {
                        $result->appendChild(new XMLElement('error', 'Could not hash `' . $filepath . '`'));
                        continue;
                    }

                    if ($this->saveIntegrityToCache($file, $filepath, $integrity)) {
                        $cache = 'saved-miss';
                    }
                }
                $xmlfile->setAttribute('filename', basename($file['file']));
                $xmlfile->setAttribute('hash', $file['hash']);
                $xmlfile->setAttribute('integrity', $integrity);
                $xmlfile->setAttribute('cache', $cache);
                $xmlfile->setValue($file['file']);

                $result->appendChild($xmlfile);
            }
        }
        catch (Exception $ex) {
            Symphony::Log()->pushExceptionToLog($ex, true);
            $result->appendChild(new XMLElement('error', General::wrapInCDATA($ex->getMessage())));
        }
        return $result;
    }

    private function getFiles()
    {
        if (!@file_exists($this->dsFilesPath)) {
            return array();
        }
        $files = array();
        $xml = @simplexml_load_file($this->dsFilesPath);
        if (!$xml) {
            throw new Exception('Could not load xml file');
        }
        $defaultHash = (string)current($xml->xpath('/*/@hash'));
        if (empty($defaultHash)) {
            $defaultHash = 'sha384';
        }
        $xmlfiles = $xml->xpath('/*/file');
        foreach ($xmlfiles as $file) {
            $fileHash = (string)current($file->xpath('@hash'));
            if (empty($fileHash)) {
                $fileHash = $defaultHash;
            }
            $files[] = array(
                'file' => (string)$file,
                             'hash' => $fileHash,
                             'namespace' => $this->dsCacheNamespace,
            );
        }
        return $files;
    }

    private function computeIntegrity(array $file, $filepath)
    {
        if (!@file_exists($filepath)) {
            return null;
        }
        $hash = @hash_file($file['hash'], $filepath, true);
        if (!$hash) {
            return null;
        }
        return $file['hash'] . '-' . base64_encode($hash);
    }

    private function createCacheKey(array $file)
    {
        return md5(implode('.', array_values($file)));
    }

    private function getCachedIntegrity(array $file, $filepath)
    {
        if (!$this->dsCache) {
            return null;
        }
        if (!@file_exists($filepath)) {
            return null;
        }
        $key = $this->createCacheKey($file);
        $cache = $this->dsCacheProvider->read($key);
        if (!$cache || !isset($cache['creation'])) {
            return null;
        }
        clearstatcache();
        if ($cache['creation'] < filemtime($filepath)) {
            return null;
        }
        return $cache['data'];
    }

    private function saveIntegrityToCache(array $file, $filepath, $integrity)
    {
        if (!$this->dsCache) {
            return false;
        }
        $key = $this->createCacheKey($file);
        if (empty($integrity)) {
            $this->dsCacheProvider->delete($key, $this->dsCacheNamespace);
            return false;
        }
        return $this->dsCacheProvider->write($key, $integrity, $this->dsCacheTTL, $this->dsCacheNamespace);
    }
}
