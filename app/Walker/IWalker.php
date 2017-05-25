<?php
/**
 * Created by PhpStorm.
 * User: malgrat
 * Date: 25.05.17
 * Time: 12:21
 */

namespace App\Walker;


interface IWalker
{
    public function setWalkModel();

    public function setSiteUrl();

    public function getPage();

}