<?php

namespace App\Providers;

use App\Walker\WalkerServiceController;
use App\Walker\IWalker;
use Illuminate\Support\ServiceProvider;

class WalkerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(WalkerServiceController::class, function(){ //IWalker::class
            return new WalkerServiceController();
        });

//        $this->app->bind('App\Helpers\RatesContract', function(){
//            return new RatesController();
//        });

//        $this->app->bind(My::class, function ($app) {
//
//            return new My ($app->make(‘User’)); // передали объект User
//});
    }
}
