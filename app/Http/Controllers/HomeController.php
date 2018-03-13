<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Jobs\TestJob1;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Jobs\TestJob;
use Illuminate\Queue\Queue;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $testjob1 = (new TestJob1(['user'=>'youxin','sex'=>1]))->delay(10)->onConnection('database');
        dispatch($testjob1);
        dd('testjob1 success');

        //dd(Carbon::now()->addMinutes(3)->addHours(8));
        #写个队列进行测试
        $job = (new TestJob())->delay(1)->onQueue('test');

        //$job = (new TestJob())->delay(1)->onConnection('database')->onQueue('test');
            //


        $this->dispatch($job);
        dd('dispatch delay success ...');

        //helper方法
        //dispatch($job);

        $job = (new TestJob());

        $this->dispatch($job);

        //$this->dispatchNow($job);

        //$this->dispatch($job);

        dd('dispatch success');

        return view('home');
    }

    public function jd(){
        dd(json_decode('{"job":"Illuminate\\Queue\\CallQueuedHandler@call","data":{"commandName":"App\\Jobs\\TestJob1","command":"O:17:\"App\\Jobs\\TestJob1\":5:{s:4:\"user\";a:2:{s:4:\"user\";s:6:\"youxin\";s:3:\"sex\";i:1;}s:10:\"connection\";s:8:\"database\";s:5:\"queue\";N;s:5:\"delay\";i:10;s:6:\"\u0000*\u0000job\";N;}"}}',true));
    }
}
