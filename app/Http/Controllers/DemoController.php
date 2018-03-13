<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\User;
use App\Users;
use Illuminate\Http\Request;
use App\Jobs\TestJob;
use Illuminate\Support\Facades\DB;

class DemoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    /**
     * 测试分布式锁
     */
    public function anyLock(){

        $user_id = 2;
        $ret = DB::transaction(function() use($user_id) {
             $user_info = Users::where(['id'=>$user_id])->lockForUpdate()->first()->toArray();
             return $user_info;
        });

        var_dump($ret);

        dd('end');
        

    }
}
