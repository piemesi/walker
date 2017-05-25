<?php
/**
 * Created by PhpStorm.
 * User: malgrat
 * Date: 25.05.17
 * Time: 12:29
 */

namespace App\Walker;


use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;


class Tui extends Walker implements IWalker
{
    const WEB_URL = 'https://tui.ru';

    const HOT_LINK = '/AllHotTours';

    const OFFER_LIST_WRAPPER = 'div#toursList',
        OFFER_LIST_ITEM = ' > article';


    private function getOfferItemLink()
    {
        $element = self::OFFER_LIST_ITEM . ' button.offers ';


    }

    protected function checkHash($key, $extendsTags = null)
    {
        $cache = Cache::store('memcached')->get($key);

//        $cache = Cache::tags([$key, $extends])->get('John', $john, $minutes);

        return $cache;
    }

    public function getPage()
    {

        $client = new Client(['base_uri' => self::WEB_URL]);

        $link   = self::WEB_URL . self::HOT_LINK;
        $params = [];
        $key    = md5($link . base64_encode(json_encode($params)));


        print($key);


//        if (!Cache::store('memcached')->has($key)) {
//            echo('EMPT2233Y!!!!');
//            $resp = $client->get($link,$params);
//            $resp = $client->request('GET', self::HOT_LINK);

        $resp = $client->request('GET', 'https://www.tui.ru/AllHotTours/', [
            // 'query' => ['page' => '1']
        ]);


        $html = $resp->getBody()->getContents();
        $page = $this->loadHtmlOfDomDocument($html);

        $offersWrap = $page->getElementById('toursList');

        $hours = Carbon::now()->addHours(2);
        Cache::store('memcached')->put($key, $html, $hours);


        print_r($offersWrap);

//        } else {
//            $html = $cachedResponse;
//        }

//        print_r(Cache::store('memcached')->get($key));

        $i     = 0;
        $items = [];
        foreach ($offersWrap->getElementsByTagName('article') as $offerItem) {
            $i++;
            foreach ($offerItem->getElementsByTagName('div') as $div) {
                $items[$i][] =
                    ['classes'   => $div->getAttribute('class'),
                     'item'      => $div,
                     'nodeName'  => $div->nodeName,
                     'nodeValue' => $div->nodeValue,
                     'nodeType'  => $div->nodeType,

                    ];
            }

            foreach ($offerItem->getElementsByTagName('img') as $div) {
                $items[$i][] =
                    ['classes'    => $div->getAttribute('class'),
                     'item'       => $div,
                     'nodeName'   => $div->nodeName,
                     'nodeValue'  => $div->nodeValue,
                     'nodeType'   => $div->nodeType,
                     'attributes' => [
                         'src'   => $div->getAttribute('src'),
                         'title' => $div->getAttribute('title'),
                         'alt'   => $div->getAttribute('alt'),
                     ]

                    ];
            }

            foreach ($offerItem->getElementsByTagName('a') as $div) {
                $items[$i][] =
                    ['classes'    => $div->getAttribute('class'),
                     'item'       => $div,
                     'nodeName'   => $div->nodeName,
                     'nodeValue'  => $div->nodeValue,
                     'nodeType'   => $div->nodeType,
                     'attributes' => [
                         'href' => $div->getAttribute('href'),

                     ]
                    ];
            }
        }
        print_r('<pre>');
        print_r($items);
        print_r('</pre>');


        die();

    }


    public function setWalkModel()
    {
        // TODO: Implement setWalkModel() method.
    }

    public function setSiteUrl()
    {
        // TODO: Implement setSiteUrl() method.
    }


}