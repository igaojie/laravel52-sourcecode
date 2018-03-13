<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

class RestartCommand extends Command
{
    /**
     * The console command name.
     * 分析并实际运行查看得到结论：这个命令是不会启动work服务的，仅仅是stop哈哈
     * https://github.com/laravel/framework/issues/11821
     * 那为什么又叫restart呢？我估计是因为有supervisor这个守护服务在，会帮忙启动的。
     * @var string
     */
    protected $name = 'queue:restart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart queue worker daemons after their current job';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        //重启 记录 illuminate:queue:restart 时间
        $this->laravel['cache']->forever('illuminate:queue:restart', time());

        $this->info('Broadcasting queue restart signal.');
    }
}
