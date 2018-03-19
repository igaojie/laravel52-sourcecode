<?php

namespace App\Jobs;

use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Log;
use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mockery\Exception;

class TestJob1 extends Job implements ShouldQueue
{
    //trait
    use InteractsWithQueue, SerializesModels;

    public $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     * 入口
     * @return void
     */
    public function handle()
    {
        print_r($this->user);
        throw new Exception('failed test');

    }

    /**
     * 自定义进入队列的方式
     */
    public function queue1(){
        Log::info(__METHOD__);
        //Log::info(var_dump(func_get_args(),true));

        //a();


    }


    public function faile1d(\Exception $e = null)
    {
        echo 'failed test failed method'.PHP_EOL;
        Log::info('failed test failed method');
        Log::info(__METHOD__);
    }
}
