<?php

require_once(TOOLKIT . '/class.datasource.php');

Class datasourceBreadcrumb extends Datasource
{
    /**
     *
     * name of the root XML element that wraps the data
     * @var string
     */
    public $dsParamROOTELEMENT = 'breadcrumb';

    /**
     *
     * Flag to detect is site is multilingual
     * Must have the and extension installed and enabled
     * @var boolean
     */
    protected $isMultiLangual = false;

    /**
     *
     * Credits method
     */
    public function about()
    {
        return array('name' => 'Breadcrumb',
                     'version' => 'Breadcrumb 1.3',
                     'release-date' => '2026-05-29',
                     'author' => array('name' => 'Sym8', 'website' => 'https://sym8.io/'));
    }

    /**
     *
     * Class constructor
     * @param object $parent
     * @param array $env
     * @param boolean $process_params
     */
    public function __construct(Array $env = null, $process_params=true)
    {
        parent::__construct($env, $process_params);

        // detect if multilangual field AND language redirect is enabled
        $this->isMultiLangual =
        (Symphony::ExtensionManager()->fetchStatus('page_lhandles') == EXTENSION_ENABLED &&
        Symphony::ExtensionManager()->fetchStatus('language_redirect') == EXTENSION_ENABLED);

        // add a ref to the Language redirect
        if ($this->isMultiLangual) {
            require_once (EXTENSIONS . '/language_redirect/lib/class.languageredirect.php');
        }
    }

    /**
     *
     * Method called by Symphony in order to build the
     * @param $param_pool
     * @return XMLElement
     */
    public function grab(?array &$param_pool = null)
    {
        // get the current page id
        $current_page_id = (int)$this->_env['param']['current-page-id'];

        // prepare output
        $result = new XMLElement($this->dsParamROOTELEMENT);

        // if multilangual extensions are enabled
        if ($this->isMultiLangual) {
            // current language
            $lang = LanguageRedirect::instance()->getLanguageCode();
        }

        // get current page title including all parents
        $titles = PageManager::resolvePage($current_page_id, isset($lang) ? 'page_lhandles_t_' . $lang : 'title');

        // get current page path including all parents
        $handles = PageManager::resolvePage($current_page_id, isset($lang) ? 'page_lhandles_h_' . $lang : 'handle');

        // generate the output
        foreach ($titles as $key => $title) {

            $path = implode('/', array_slice($handles, 0, $key + 1));

            $result->appendChild(new XMLElement( 'page', htmlentities($title), array('path'=>$path) ));
        }

        // return xml result set
        return $result;
    }
}
