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
<<<<<<< HEAD
Route::get('test', 'TestController@showProfile');
=======

Route::auth();

Route::get('/home', 'HomeController@index');
<<<<<<< HEAD
Route::controller('/demo', 'DemoController');

=======
>>>>>>> 34b22114350c15bbc9ef7e0de6e061079ea37298
>>>>>>> 33c54b37c4fd65cefd47c601d67e52e1ca5c6fe4
