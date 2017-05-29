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

    private $acPax = ['2AD' => 2, '1AD' => 1];


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

        $currency_pattern = '/\d|\.|\s/';

        $i        = 0;
        $items    = [];
        $gathered = [];
        foreach ($offersWrap->getElementsByTagName('article') as $offerItem) {
            $i++;
            foreach ($offerItem->getElementsByTagName('div') as $div) {

                if ($div->getAttribute('class') == 'price') {
                    $gathered[$i]['gathered']['price']    = (int)preg_replace('/\D/', '', $div->nodeValue);
                    $gathered[$i]['gathered']['currency'] = trim(preg_replace($currency_pattern, '', $div->nodeValue));
                }

                if ($div->getAttribute('class') == 'cityName') {
                    $gathered[$i]['gathered']['region'] = $div->nodeValue;
                }

                if ($div->getAttribute('class') == 'tourStandart') {
                    $gathered[$i]['gathered']['ac']  = trim($div->nodeValue);
                    $gathered[$i]['gathered']['pax'] = $this->acPax[trim($div->nodeValue)] ?? null;

                }

                if ($div->getAttribute('class') == 'hotelType') {
                    $gathered[$i]['gathered']['meal'] = trim($div->nodeValue);
                }

                if ($div->getAttribute('class') == 'tourDuration') {
                    $gathered[$i]['gathered']['nights'] = (int)$div->nodeValue;
                }

                if ($div->getAttribute('class') == 'flightsOut') {
                    $gathered[$i]['gathered']['start']['text'] = (int)$div->nodeValue;
                    $gathered[$i]['gathered']['start']['wd']   = preg_replace('/(\w*)/i', '$0', $div->nodeValue);
                    preg_match('/\d{2}.\d{2}/', $div->nodeValue, $dayText);

                    $gathered[$i]['gathered']['start']['dayText'] = $dayText[0] ?? null;
                    $dA                                           = explode('.', $gathered[$i]['gathered']['start']['dayText']);
                    $gathered[$i]['gathered']['start']['day']     = (int)$dA[0];
                    $gathered[$i]['gathered']['start']['month']   = (int)($dA[1] ?? null);
                }

                if ($div->getAttribute('class') == 'flightsIn') {
                    $gathered[$i]['gathered']['finish']['text']    = (int)$div->nodeValue;
                    $gathered[$i]['gathered']['finish']['wd']      = preg_replace('/(\w*)/i', '$0', $div->nodeValue);
                    $gathered[$i]['gathered']['finish']['dayText'] = preg_replace('/(\d{2}\.\d{2})/i', '$0', $div->nodeValue);
                    $dA                                            = explode('.', $gathered[$i]['gathered']['finish']['dayText']);
                    $gathered[$i]['gathered']['finish']['day']     = (int)$dA[0];
                    $gathered[$i]['gathered']['finish']['month']   = (int)$dA[1];
                }


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

                if ($div->nodeName == 'img') {
                    $gathered[$i]['gathered']['img']['main']  = 'https://tui.ru' . $div->getAttribute('src');
                    $gathered[$i]['gathered']['img']['x_33x'] = 'https://tui.ru' . $div->getAttribute('src');

                    $x150 = str_replace('width=33', 'width=150', $div->getAttribute('src'));
                    $x150 = str_replace('height=33', 'height=150', $x150);

                    $gathered[$i]['gathered']['img']['x150'] = 'https://tui.ru' . $x150;
                }
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


                if (empty($div->getAttribute('class'))) {
                    $gathered[$i]['gathered']['hotelLink'] = 'https://tui.ru' . $div->getAttribute('href');

                }

                if ($div->getAttribute('class') == 'tourName') {
                    $gathered[$i]['gathered']['tourName'] = trim($div->nodeValue);

                    $gathered[$i]['gathered']['tourLink'] = 'https://tui.ru' . $div->getAttribute('href');
                }

            }
        }
        print_r('<pre>');
        print_r($gathered);
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