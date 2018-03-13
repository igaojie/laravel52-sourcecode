<?php

namespace App\Http\Controllers;

use App\Http\Requests;
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
}
