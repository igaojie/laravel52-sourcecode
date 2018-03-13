<?php

namespace Illuminate\Bus;

use Closure;
use RuntimeException;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Bus\QueueingDispatcher;

class Dispatcher implements QueueingDispatcher
{
    /**
     * The container implementation.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The pipeline instance for the bus.
     *
     * @var \Illuminate\Pipeline\Pipeline
     */
    protected $pipeline;

    /**
     * The pipes to send commands through before dispatching.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The queue resolver callback.
     *
     * @var \Closure|null
     */
    protected $queueResolver;

    /**
     * Create a new command dispatcher instance.
     * 创建一个新的命令分发器实例
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Closure|null  $queueResolver
     * @return void
     */
    public function __construct(Container $container, Closure $queueResolver = null)
    {
        $this->container = $container;
        $this->queueResolver = $queueResolver;
        $this->pipeline = new Pipeline($container);
    }

    /**
     * Dispatch a command to its appropriate handler.
     * 分发一个命令到适当的处理模块
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        //放入队列
        if ($this->queueResolver && $this->commandShouldBeQueued($command)) {
            return $this->dispatchToQueue($command);
        } else {
            //立即执行 不进入队列（同步）
            return $this->dispatchNow($command);
        }
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchNow($command)
    {
        return $this->pipeline->send($command)->through($this->pipes)->then(function ($command) {
            return $this->container->call([$command, 'handle']);
        });
    }

    /**
     * Determine if the given command should be queued.
     * 判断参数是否是走队列
     * @param  mixed  $command
     * @return bool
     */
    protected function commandShouldBeQueued($command)
    {
        return $command instanceof ShouldQueue;
    }

    /**
     * Dispatch a command to its appropriate handler behind a queue.
     * 分发命令到对应的消息队列中
     * @param  mixed  $command
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function dispatchToQueue($command)
    {
        //dd($command);
        // 如果指定了连接器 那么就使用
        $connection = isset($command->connection) ? $command->connection : null;

        //laravel里内置了多种队列服务,这里则解析出来 通过$connection来确定$queue
        //"Illuminate\Queue\DatabaseQueue"
        $queue = call_user_func($this->queueResolver, $connection);

        //队列服务解析不成功则抛出异常
        if (! $queue instanceof Queue) {
            throw new RuntimeException('Queue resolver did not return a Queue implementation.');
        }

        //可以在Jobs文件里自定义queue 方法来实现入库
        if (method_exists($command, 'queue')) {
            return $command->queue($queue, $command);
        } else {
            //使用框架默认的方式
            return $this->pushCommandToQueue($queue, $command);
        }
    }

    /**
     * Push the command onto the given queue instance.
     * 推送一条命令到消息队列实例
     * @param  \Illuminate\Contracts\Queue\Queue  $queue 队列服务
     * @param  mixed  $command 任务类
     * @return mixed
     *
     * 根据不同的任务属性选择不同的入队列方式
     */
    protected function pushCommandToQueue($queue, $command)
    {
        if (isset($command->queue, $command->delay)) {
            return $queue->laterOn($command->queue, $command->delay, $command);
        }

        if (isset($command->queue)) {
            return $queue->pushOn($command->queue, $command);
        }

        if (isset($command->delay)) {
            return $queue->later($command->delay, $command);
        }

        return $queue->push($command);
    }

    /**
     * Set the pipes through which commands should be piped before dispatching.
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        $this->pipes = $pipes;

        return $this;
    }
}
