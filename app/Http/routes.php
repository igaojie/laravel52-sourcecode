<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
//var_dump(env('LARAVELBLOG_KEY'));
//print_r($_SERVER);
Route::get('/', function () {
    return view('welcome');
});
Route::get('test', 'TestController@showProfile');
Route::auth();

Route::get('/home', 'HomeController@index');
Route::controller('/demo', 'DemoController');
