<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Jobs\TestJob;

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
        #写个队列进行测试
        $job = (new TestJob())->delay(1);

        $this->dispatch($job);

        $job = (new TestJob());

        $this->dispatch($job);

        //$this->dispatchNow($job);


        dd('dispatch success');

        return view('home');
    }
}
