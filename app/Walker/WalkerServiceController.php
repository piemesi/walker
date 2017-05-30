<?php
/**
 * Created by PhpStorm.
 * User: malgrat
 * Date: 25.05.17
 * Time: 13:23
 */

namespace App\Walker;


class WalkerServiceController
{
    /** @var  IWalker $tui */
    public $tui;

    function __construct()
    {
        $this->tui = new Tui();
    }

//    public function getPage(){
//
//
//
//    }
}