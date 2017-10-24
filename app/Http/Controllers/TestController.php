<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use Illuminate\Support\Facades\Log;

class TestController extends BaseController
{
    use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

    /**
     * @author shaogaojie@xin.com
     * 测试
     */
    public function showProfile(){

        $error = ['msg'=>'this is a test log'];
        Log::info($error);
        //debug_print_backtrace();
        dd(__FUNCTION__);
    }
}
