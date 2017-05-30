<?php

namespace App\Http\Controllers;

use App\Walker\WalkerServiceController;
use Illuminate\Http\Request;

class WalkerController extends Controller
{
    public function tui(WalkerServiceController $walkerService){

//        $walkerService->tui->getData();
        $walkerService->tui->getOfferItem();

        return view('walker.index',['siteName'=>'tui']);
    }
}
