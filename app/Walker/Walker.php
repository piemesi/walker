<?php


namespace App\Walker;


use GuzzleHttp\Client;

class Walker
{
    protected $client;

    function __construct()
    {
        $this->client = new Client(['base_uri' => static::WEB_URL]);
    }

    protected function loadHtmlOfDomDocument(string $html)
    {
        // @see http://stackoverflow.com/questions/11819603/dom-loadhtml-doesnt-work-properly-on-a-server
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);

        return $dom;

//        $internalErrors = libxml_use_internal_errors(true);
//        $disableEntities = libxml_disable_entity_loader(true);
//        libxml_clear_errors();
//
//        $dom = new \DOMDocument();
//        $dom->loadHTML($html);
//
//        libxml_use_internal_errors($internalErrors);
//        libxml_disable_entity_loader($disableEntities);
//
//        if ($error = libxml_get_last_error()) {
//            libxml_clear_errors();
//
//            throw new Exception($error->message);
//        }
    }


}