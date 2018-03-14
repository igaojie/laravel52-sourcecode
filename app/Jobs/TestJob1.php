<?php

namespace App\Jobs;

use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Log;
use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\QueueServiceProvider;

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

    }

    /**
     * 自定义进入队列的方式
     */
    public function queue(){
        Log::info(__METHOD__);
        Log::info(var_dump(func_get_args(),true));

        //a();


    }
}
