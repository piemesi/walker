<?php

Artisan::call('cache:clear');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


//Route::get('/walker', \App\Http\Controllers\WalkerController::class)->name('walker');


Route::group(['prefix' => 'walker'], function () {
    Route::get('tui', 'WalkerController@tui')->name('tui');
});
