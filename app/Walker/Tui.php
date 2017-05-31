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

    private $pageArr = [];
    private $lastPage = 0;


    private function getOfferItemLink()
    {
        $element = self::OFFER_LIST_ITEM . ' button.offers ';


    }

    public function getOfferItem()
    {
        $this->getOfferItemPage();
    }

    private function getOfferItemPage()
    {
//        https://www.tui.ru/Tours/Europe/Turkey/Dalaman/Fethiye/Sarigerme/TUI-Magic-Life-Sarigerme/#/tabs
//        https://www.tui.ru/Tours/Europe/Montenegro/Budvanska-Rivijera/Budva-city/budva/apartments-dimic-ellite/#?DepartureDate=2017-06-04%7C2017-06-04&AdultCount=2&NightsFrom=7&NightsTo=7


        $client = new Client(['base_uri' => self::WEB_URL]);

        $link = 'https://www.tui.ru/Tours/Europe/Montenegro/Budvanska-Rivijera/Budva-city/budva/apartments-dimic-ellite/#?DepartureDate=2017-06-04%7C2017-06-04&AdultCount=2&NightsFrom=7&NightsTo=7';//self::WEB_URL . self::HOT_LINK;
        $link = 'https://www.tui.ru/Tours/Europe/Turkey/Antalya/belek/Belek-Center/TUI-Magic-Life-Belek/#?DepartureDate=2017-06-01%7C2017-06-01&AdultCount=2&NightsFrom=4&NightsTo=4';

        $hotelHash = base64_encode('/Tours/Europe/Turkey/Antalya/belek/Belek-Center/TUI-Magic-Life-Belek/');
        $hotelName = 'TUI Magic Life Belek';

        $hotelData = [
            'hash'     => $hotelHash,
            'title'    => $hotelName,
            'operator' => 'tui',
            'baseHash' => '',
            'images'   => []
        ];

        $params = [];
        $key    = md5($link . base64_encode(json_encode($params)));


        print($key);


//        if (!Cache::store('memcached')->has($key)) {
//            echo('EMPT2233Y!!!!');
//            $resp = $client->get($link,$params);
//            $resp = $client->request('GET', self::HOT_LINK);


        $resp = $client->request('GET', $link, []);

//        if($pageNum) {
//
//            echo 'pageNum='.$pageNum;
//            print_r($resp);
//            die();
//        }


        $html = $resp->getBody()->getContents();
        $page = $this->loadHtmlOfDomDocument($html);

        /// images
        $imageContainer = $page->getElementById('imagesConteiner');
        $i              = 0;
        foreach ($imageContainer->getElementsByTagName('img') as $imgItem) {
            $items[$i] =
                ['classes'    => $imgItem->getAttribute('class'),
                 'item'       => $imgItem,
                 'nodeName'   => $imgItem->nodeName,
                 'nodeValue'  => $imgItem->nodeValue,
                 'nodeType'   => $imgItem->nodeType,
                 'attributes' => [
                     'src'   => $imgItem->getAttribute('src'),
                     'title' => $imgItem->getAttribute('title'),
                     'alt'   => $imgItem->getAttribute('alt'),
                 ]

                ];

            if ($imgItem->nodeName == 'img') {
                $items[$i]['gathered']['img']['main']  = 'https://tui.ru' . $imgItem->getAttribute('src');
                $items[$i]['gathered']['img']['x_33x'] = 'https://tui.ru' . $imgItem->getAttribute('src');

                $x150                                 = preg_replace('/width=([0-9]*)/', 'width=150', $imgItem->getAttribute('src'));
                $x150                                 = preg_replace('/height=([0-9]*)/', 'height=150', $x150);
                $items[$i]['gathered']['img']['x150'] = 'https://tui.ru' . $x150;

                $x620x380                                 = preg_replace('/width=([0-9]*)/', 'width=620', $imgItem->getAttribute('src'));
                $x620x380                                 = preg_replace('/height=([0-9]*)/', 'height=380', $x620x380);
                $items[$i]['gathered']['img']['x620x380'] = 'https://tui.ru' . $x620x380;
            }

            $i++;
        }
/// images

        echo '-----> Images: \n';
        print_r($items);
        $hotelData['images']      = $items;
        $hotelData['hash_images'] = base64_encode(json_encode($items));

        // brief facilities
        $nextDiv = $page->getElementsByTagName('div'); //$imageContainer->nextSibling;
//        if($nextDiv->)
        $wrapInfo        = $page->getElementById('p_lt_zoneContent_pageplaceholder_p_lt_pnlTabsContainer');
        $hotelFacilities = [];
        $brief           = '';
        $taLink          = [];
        $ta              = [];
        $tui             = [];
        foreach ($wrapInfo->getElementsByTagName('div') as $nextDiv) {

            if ($nextDiv->getAttribute('class') == 'hotelDescription') {

                foreach ($nextDiv->getElementsByTagName('div') as $desDiv) {
                    if ($desDiv->getAttribute('class') == 'description') {
                        $brief = $desDiv->nodeValue;
                    }
                }

                foreach ($nextDiv->getElementsByTagName('ul') as $facilitiesDiv) {
                    if ($facilitiesDiv->getAttribute('class') == 'facilities') {
                        $liItem = 0;
                        foreach ($facilitiesDiv->getElementsByTagName('li') as $li) {
                            $txt = '';
                            foreach ($li->getElementsByTagName('span') as $span) {
                                $txt .= $span->nodeValue;
                            }

                            $hotelFacilities[$liItem] = $txt;
                            $liItem++;
                        }
                    }
                }
                echo '----->  hotelFacilities: \n';
                print_r($hotelFacilities);
                echo '----->  brief: \n';
                print_r($brief);

                $hotelData['facilities']      = $hotelFacilities;
                $hotelData['hash_facilities'] = base64_encode(json_encode($hotelFacilities));

                $hotelData['brief']      = $brief;
                $hotelData['hash_brief'] = base64_encode($brief);

            }


            if ($nextDiv->getAttribute('class') == 'hotelResponses') {
                foreach ($nextDiv->getElementsByTagName('div') as $reviewerDiv) {
                    if ($reviewerDiv->getAttribute('class') == 'TuiTripadvisor') {
                        foreach ($reviewerDiv->getElementsByTagName('dt') as $dt) {
                            $dd   = $dt->nextSibling;
                            $ta[] = ['class' => $dt->getAttribute('class'), 'name' => $dd->nodeValue];
                        }

                        foreach ($reviewerDiv->getElementsByTagName('a') as $div) {
                            if ($reviewerDiv->getAttribute('class') == 'adviserLink') {
                                $taLink =
                                    ['classes'    => $div->getAttribute('class'),
                                     'nodeName'   => $div->nodeName,
                                     'nodeValue'  => $div->nodeValue,
                                     'nodeType'   => $div->nodeType,
                                     'attributes' => [
                                         'href' => $div->getAttribute('href'),

                                     ]
                                    ];
                            }

                        }


                    }

                    if ($reviewerDiv->getAttribute('class') == 'tuiRating') {
                        foreach ($reviewerDiv->getElementsByTagName('span') as $span) {
                            if ($span->getAttribute('itemprop') === 'average') {
                                $tui['average'] = $span->nodeValue;
                            }

                        }

                        foreach ($reviewerDiv->getElementsByTagName('a') as $aItem) {
                            if ($aItem->getAttribute('class') === 'countComments') {
                                $tui['link'] = $aItem->nodeValue;
                                $tui['href'] = $aItem->getAttribute('href');
                            }

                        }
                    }
                }
                echo '-----> TaLink: \n';
                print_r($taLink);
                echo '-----> Ta: \n';
                print_r($ta);
                echo '-----> Tui: \n';
                print_r($tui);

                $hotelData['trip_adviser']['link']      = $taLink;
                $hotelData['trip_adviser']['condition'] = $ta;
                $hotelData['hash_trip_adviser']         = base64_encode(json_encode($hotelData['trip_adviser']));

                $hotelData['tui_reviewer']      = $tui;
                $hotelData['hash_tui_reviewer'] = base64_encode(json_encode($tui));


            }


        }

        $tabs = [];
//        $descriptionsTabs = $page->getElementById('p_lt_zoneContent_pageplaceholder_p_lt_pnlTabsContainer');
//        foreach ($page->getElementsByTagName('ul') as $ulD){
//
//            /** @var \DOMElement $ulD  */
//
////            if($ulD->localName) {
////                print_r($ulD->nodeValue);
////            }
//            print_r("|||" ."||");
//
//            print_r(  $ulD->getAttributeNode('li') );
//
//            if($ulD->getAttribute('id') == 'hotel_descriptions_tabs') {
//                print_r("<pre>");print_r($ulD);print_r("</pre>");
//                foreach ($ulD->getElementsByTagName('li') as $tabLi) {
//                    $tabs[]['title'] = $tabLi->nodeValue;
//                }
//            }
//        }
//
//
//       print_r('sssssssssssssssssssss');
//       print_r($tabs); die();


        foreach ($page->getElementsByTagName('ul') as $ul) {
            if ($ul->getAttribute('class') == 'holder') {
                $liN = 0;
                foreach ($ul->getElementsByTagName('li') as $li) {
//                    $liTabs[] = $li->nodeValue;
//                    if (isset($tabs[$liN])) {
                    $tabs[$liN]['val_whole'] = $li->nodeValue;


                    //facilityDescription
                    //  $pN = 0;
                    foreach ($li->getElementsByTagName('p') as $p) {
                        if ($p->getAttribute('class') == 'facilityDescription') {

                            $tabs[$liN]['val']['body'] = strip_tags($p->nodeValue, 'br,em'); //[$pN]
                            //         $pN++;
                        }
                    }

                    // $h4N = 0;
                    foreach ($li->getElementsByTagName('h4') as $h4) {
                        $tabs[$liN]['val']['header']         = $h4->nodeValue; //[$h4N]
                        $notProcessedHeaders[$h4->nodeValue] = $h4->nodeValue;

                        // $h4N++;
                    }


                    $liN++;
                }

                // }


            }
        }
        print_r("<pre>");
        print_r($tabs);
        print_r("</pre>");
        // review

        foreach ($tabs as $tab) {
            if ($tab['val']['header'] == 'Размещение') {

                preg_match_all('/(\d+)\s+номер/i', $tab['val']['body'], $matches);


                if (isset($matches[1])) {
                    $roomsVars = $matches[1];
                    rsort($roomsVars);
                    $hotelData['suggest_roomsNum'] = $roomsVars[0];
                }
                unset($notProcessedHeaders[$tab['val']['header']]);

            }

            if (stripos($tab['val']['header'], 'рестораны') > -1 || stripos($tab['val']['header'], 'бары') > -1) {
                $this->NextRowRegexpPattern($tab['val']['body'], 'suggest_restaurants', $hotelData);
                unset($notProcessedHeaders[$tab['val']['header']]);

            }

            if ($tab['val']['header'] == 'В номере') {

                $this->NextRowRegexpPattern($tab['val']['body'], 'suggest_in_rooms', $hotelData);
                unset($notProcessedHeaders[$tab['val']['header']]);
            }

            if ($tab['val']['header'] == 'Территория') {

                $this->NextRowRegexpPattern($tab['val']['body'], 'suggest_territory', $hotelData);
                unset($notProcessedHeaders[$tab['val']['header']]);
            }

            if ($tab['val']['header'] == 'Развлечения и спорт') {

                $this->NextRowRegexpPattern($tab['val']['body'], 'suggest_sport_animation', $hotelData);
                unset($notProcessedHeaders[$tab['val']['header']]);
            }

            if ($tab['val']['header'] == 'Для детей') {

                $this->NextRowRegexpPattern($tab['val']['body'], 'suggest_for_children', $hotelData);
                unset($notProcessedHeaders[$tab['val']['header']]);
            }

            if ($tab['val']['header'] == 'Питание') {

                $this->NextRowRegexpPattern($tab['val']['body'], 'suggest_meal_info', $hotelData);
                unset($notProcessedHeaders[$tab['val']['header']]);
            }
        }

        print_r("<pre>");
        print_r($hotelData);
        print_r("</pre>");

        print_r("<pre>");
        print_r($notProcessedHeaders);
        print_r("</pre>");


//        $hours = Carbon::now()->addHours(2);
//        Cache::store('memcached')->put($key, $html, $hours);
//
//
////        print_r($offersWrap);
//
////        } else {
////            $html = $cachedResponse;
////        }
//
////        print_r(Cache::store('memcached')->get($key));
//
//        if(!$pageNum) {
//            $pages = [];
//            foreach ($offersWrap->getElementsByTagName('nav') as $naxItem) {
//
//                if ($naxItem->getAttribute('class') == 'paging') {
//                    foreach ($naxItem->getElementsByTagName('a') as $aItem) {
//
//                        preg_match('/([0-9])/',$aItem->nodeValue,$matches);
//                        if( isset($matches[1]))
//                        {
//                            $pages[$aItem->nodeValue] = [ 'href' => $aItem->getAttribute('href')];
//                        }
//                    }
//                }
//            }
//
//            $pagesKeys = array_keys($pages);
//            rsort($pagesKeys);
//            $this->lastPage = $pagesKeys[0];
//            print_r($pages);
//        }
//
//
//        $currency_pattern = '/\d|\.|\s/';
//
//        $i        = 0;
//        $items    = [];
//        $gathered = [];
//        foreach ($offersWrap->getElementsByTagName('article') as $offerItem) {
//            $i++;
//            foreach ($offerItem->getElementsByTagName('div') as $div) {
//
//                if ($div->getAttribute('class') == 'price') {
//                    $gathered[$i]['gathered']['price']    = (int)preg_replace('/\D/', '', $div->nodeValue);
//                    $gathered[$i]['gathered']['currency'] = trim(preg_replace($currency_pattern, '', $div->nodeValue));
//                }
//
//                if ($div->getAttribute('class') == 'cityName') {
//                    $gathered[$i]['gathered']['region'] = $div->nodeValue;
//                }
//
//                if ($div->getAttribute('class') == 'tourStandart') {
//                    $gathered[$i]['gathered']['ac']  = trim($div->nodeValue);
//                    $gathered[$i]['gathered']['pax'] = $this->acPax[trim($div->nodeValue)] ?? null;
//
//                }
//
//                if ($div->getAttribute('class') == 'hotelType') {
//                    $gathered[$i]['gathered']['meal'] = trim($div->nodeValue);
//                }
//
//                if ($div->getAttribute('class') == 'tourDuration') {
//                    $gathered[$i]['gathered']['nights'] = (int)$div->nodeValue;
//                }
//
//                if ($div->getAttribute('class') == 'flightsOut') {
//                    $gathered[$i]['gathered']['start']['text'] = (int)$div->nodeValue;
//                    $gathered[$i]['gathered']['start']['wd']   = preg_replace('/(\w*)/i', '$0', $div->nodeValue);
//                    preg_match('/\d{2}.\d{2}/', $div->nodeValue, $dayText);
//
//                    $gathered[$i]['gathered']['start']['dayText'] = $dayText[0] ?? null;
//                    $dA                                           = explode('.', $gathered[$i]['gathered']['start']['dayText']);
//                    $gathered[$i]['gathered']['start']['day']     = (int)$dA[0];
//                    $gathered[$i]['gathered']['start']['month']   = (int)($dA[1] ?? null);
//                }
//
//                if ($div->getAttribute('class') == 'flightsIn') {
//                    $gathered[$i]['gathered']['finish']['text']    = (int)$div->nodeValue;
//                    $gathered[$i]['gathered']['finish']['wd']      = preg_replace('/(\w*)/i', '$0', $div->nodeValue);
//                    $gathered[$i]['gathered']['finish']['dayText'] = preg_replace('/(\d{2}\.\d{2})/i', '$0', $div->nodeValue);
//                    $dA                                            = explode('.', $gathered[$i]['gathered']['finish']['dayText']);
//                    $gathered[$i]['gathered']['finish']['day']     = (int)$dA[0];
//                    $gathered[$i]['gathered']['finish']['month']   = (int)$dA[1];
//                }
//
//
//                $items[$i][] =
//                    ['classes'   => $div->getAttribute('class'),
//                     'item'      => $div,
//                     'nodeName'  => $div->nodeName,
//                     'nodeValue' => $div->nodeValue,
//                     'nodeType'  => $div->nodeType,
//
//                    ];
//            }
//
//            foreach ($offerItem->getElementsByTagName('img') as $div) {
//                $items[$i][] =
//                    ['classes'    => $div->getAttribute('class'),
//                     'item'       => $div,
//                     'nodeName'   => $div->nodeName,
//                     'nodeValue'  => $div->nodeValue,
//                     'nodeType'   => $div->nodeType,
//                     'attributes' => [
//                         'src'   => $div->getAttribute('src'),
//                         'title' => $div->getAttribute('title'),
//                         'alt'   => $div->getAttribute('alt'),
//                     ]
//
//                    ];
//
//                if ($div->nodeName == 'img') {
//                    $gathered[$i]['gathered']['img']['main']  = 'https://tui.ru' . $div->getAttribute('src');
//                    $gathered[$i]['gathered']['img']['x_33x'] = 'https://tui.ru' . $div->getAttribute('src');
//
//                    $x150 = str_replace('width=33', 'width=150', $div->getAttribute('src'));
//                    $x150 = str_replace('height=33', 'height=150', $x150);
//
//                    $gathered[$i]['gathered']['img']['x150'] = 'https://tui.ru' . $x150;
//                }
//            }
//
//            foreach ($offerItem->getElementsByTagName('a') as $div) {
//                $items[$i][] =
//                    ['classes'    => $div->getAttribute('class'),
//                     'item'       => $div,
//                     'nodeName'   => $div->nodeName,
//                     'nodeValue'  => $div->nodeValue,
//                     'nodeType'   => $div->nodeType,
//                     'attributes' => [
//                         'href' => $div->getAttribute('href'),
//
//                     ]
//                    ];
//
//
//                if (empty($div->getAttribute('class'))) {
//                    $gathered[$i]['gathered']['hotelLink'] = 'https://tui.ru' . $div->getAttribute('href');
//
//                }
//
//                if ($div->getAttribute('class') == 'tourName') {
//                    $gathered[$i]['gathered']['tourName'] = trim($div->nodeValue);
//
//                    $gathered[$i]['gathered']['tourLink'] = 'https://tui.ru' . $div->getAttribute('href');
//                }
//
//            }
//        }


        die();
    }


    private function NextRowRegexpPattern($data, $key, &$hotelData)
    {
//        print_r($data);
//        print_r('-------ssss');
        preg_match_all('/([^\n]*)\n/i', $data, $matches);

        $isNextPaid = null;

        if (isset($matches[1])) {
            foreach ($matches[1] as $restik) {
                preg_match('/([^\(]+)(\(([^\)]*)\))?/', $restik, $restDesc);
                $restikInfo = null;
                $addItional = [];
                if (isset($restDesc[1])) {
                    $restikName = trim($restDesc[1]);

                    if (isset($restDesc[3])) {
                        $restikInfo = $restDesc[3];
                        preg_match('/\W?(платно|бесплатно)/iu', $restikInfo, $isPaidInfo);
                        if (isset($isPaidInfo[1])) {
                            if (strtolower($isPaidInfo[1]) == 'платно') {
                                $addItional['isPaid'] = 1;
                            } else {
                                $addItional['isForFree'] = 1;
                            }
                        }
                    }


                } else {
                    $restikName = trim($restik);

                }


                if (!empty($restikName)) {
                    $restikName = preg_replace('/^[\S]{0,3}-/', '', $restikName);

//                    preg_match('/^\s?(\d+)\s(.*)/iu',$restikName, $originNumArr);
//                    print_r('<br>-------->>>');
//                    print_r($originNumArr);
                    if (isset($originNumArr[1]) && isset($originNumArr[2])) {

//                        $getMorpherResp = $this->client->request('GET', 'https://ws3.morpher.ru/russian/declension?format=json&s='.$originNumArr[2], []);

//$respJsonD = json_decode($getMorpherResp->getBody()->getContents(), true);

//                        print_r($respJsonD['множественное']);


                        $addItional['origin_title'] = $restikName;
                        $addItional['seems_amount'] = $originNumArr[1];
                        $restikName                 = $originNumArr[2];
                    }

                    $data = ['title' => $restikName, 'info' => $restikInfo];

                    if (count($addItional)) {
                        $data = array_merge($data, $addItional);
                    }

                    if (!is_null($isNextPaid)) {
                        $data['seems_paid_free'] = $isNextPaid;
                    }
                    $hotelData[$key][] = $data;
                }

                preg_match('/(платно|бесплатно)\s?\:(.*)/iu', $restikName, $isNextPaidArr);
                if (isset($isNextPaidArr[1])) {
                    $isNextPaid                                                         = (strtolower($isNextPaidArr[1]) == 'платно') ? -1 : 1;
                    $hotelData[$key][count($hotelData[$key]) - 1]['seems_proper_title'] = str_replace($isNextPaidArr[0], '', $restikName);

                    if(isset($isNextPaidArr[2]) && !empty($isNextPaidArr[2])) {
                        $dataNote = ['title' => $isNextPaidArr[2], 'info' => '', 'seems_note'=> 1];
                        if (!is_null($isNextPaid)) {
                            $dataNote['seems_paid_free'] = $isNextPaid;
                        }
                        $hotelData[$key][] = $dataNote;

                    }
                }

            }
        }


    }

    public function getData()
    {
        $this->getPage();

        if ($this->lastPage) {
//            echo('lp='.$this->lastPage);
            for ($i = 2; $i <= $this->lastPage; $i++) {
                $this->getPage($i);
            }
        }


//        print_r($this->pageArr);

        Cache::store('memcached')->put('offersData', $this->pageArr, $hours = Carbon::now()->addHours(2));

        file_put_contents(__DIR__ . '/../../storage/logs/offersData', print_r($this->pageArr, 1));

        die();
    }

    protected function checkHash($key, $extendsTags = null)
    {
        $cache = Cache::store('memcached')->get($key);

//        $cache = Cache::tags([$key, $extends])->get('John', $john, $minutes);

        return $cache;
    }


    private function pagesCycle()
    {


        $this->getPage();

    }


    public function getPage($pageNum = null)
    {
        $cachedData = Cache::store('memcached')->get('offersData'); //+$pageNum
//        if($cachedData){
//            die();
//        }

        $client = new Client(['base_uri' => self::WEB_URL]);

        $link    = self::WEB_URL . self::HOT_LINK;
        $params  = [];
        $pageArr = $pageNum ? ['query' => ['page' => $pageNum]] : ['query' => ['page' => 1]];
        $params  = $pageArr;
        $key     = md5($link . base64_encode(json_encode($params)));


        print($key);


//        if (!Cache::store('memcached')->has($key)) {
//            echo('EMPT2233Y!!!!');
//            $resp = $client->get($link,$params);
//            $resp = $client->request('GET', self::HOT_LINK);


        $resp = $client->request('GET', 'https://www.tui.ru/AllHotTours/', $pageArr);

//        if($pageNum) {
//
//            echo 'pageNum='.$pageNum;
//            print_r($resp);
//            die();
//        }


        $html = $resp->getBody()->getContents();
        $page = $this->loadHtmlOfDomDocument($html);

        $offersWrap = $page->getElementById('toursList');

        $hours = Carbon::now()->addHours(2);
        Cache::store('memcached')->put($key, $html, $hours);


//        print_r($offersWrap);

//        } else {
//            $html = $cachedResponse;
//        }

//        print_r(Cache::store('memcached')->get($key));

        if (!$pageNum) {
            $pages = [];
            foreach ($offersWrap->getElementsByTagName('nav') as $naxItem) {

                if ($naxItem->getAttribute('class') == 'paging') {
                    foreach ($naxItem->getElementsByTagName('a') as $aItem) {

                        preg_match('/([0-9])/', $aItem->nodeValue, $matches);
                        if (isset($matches[1])) {
                            $pages[$aItem->nodeValue] = ['href' => $aItem->getAttribute('href')];
                        }
                    }
                }
            }

            $pagesKeys = array_keys($pages);
            rsort($pagesKeys);
            $this->lastPage = $pagesKeys[0];
            print_r($pages);
        }


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
//        print_r('<pre>');
//        print_r($gathered);
//        print_r('</pre>');

        $pageItem = $pageNum ? $pageNum : 1;
        print('---->');
        print_r($pageItem);
        print('<----');
        $this->pageArr[$pageItem] = $gathered;


//        die();

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