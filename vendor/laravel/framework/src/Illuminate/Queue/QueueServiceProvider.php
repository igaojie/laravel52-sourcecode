<?php

namespace Illuminate\Queue;

use IlluminateQueueClosure;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Queue\Console\ListenCommand;
use Illuminate\Queue\Console\RestartCommand;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\Connectors\NullConnector;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Connectors\RedisConnector;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Queue\Connectors\DatabaseConnector;
use Illuminate\Queue\Connectors\BeanstalkdConnector;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     * 注册服务提供者
     * @return void
     */
    public function register()
    {
        //注册Manager, 而Manager为队列服务的统一入口
        $this->registerManager();

        //注册队列的各种命令
        $this->registerWorker();

        $this->registerListener();

        // 注册任务执行失败后的记录
        $this->registerFailedJobServices();

        $this->registerQueueClosure();
    }

    /**
     * Register the queue manager.
     * 注册消息队列控制器
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('queue', function ($app) {
            // Once we have an instance of the queue manager, we will register the various
            // resolvers for the queue connectors. These connectors are responsible for
            // creating the classes that accept queue configs and instantiate queues.
            $manager = new QueueManager($app);

            $this->registerConnectors($manager);

            return $manager;
        });

        $this->app->singleton('queue.connection', function ($app) {
            return $app['queue']->connection();
        });
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerWorker()
    {
        $this->registerWorkCommand();

        $this->registerRestartCommand();

        $this->app->singleton('queue.worker', function ($app) {
            return new Worker($app['queue'], $app['queue.failer'], $app['events']);
        });
    }

    /**
     * Register the queue worker console command.
     *
     * @return void
     */
    protected function registerWorkCommand()
    {
        $this->app->singleton('command.queue.work', function ($app) {
            return new WorkCommand($app['queue.worker']);
        });

        $this->commands('command.queue.work');
    }

    /**
     * Register the queue listener.
     *
     * @return void
     */
    protected function registerListener()
    {
        $this->registerListenCommand();

        $this->app->singleton('queue.listener', function ($app) {
            return new Listener($app->basePath());
        });
    }

    /**
     * Register the queue listener console command.
     *
     * @return void
     */
    protected function registerListenCommand()
    {
        $this->app->singleton('command.queue.listen', function ($app) {
            return new ListenCommand($app['queue.listener']);
        });

        $this->commands('command.queue.listen');
    }

    /**
     * Register the queue restart console command.
     *
     * @return void
     */
    public function registerRestartCommand()
    {
        $this->app->singleton('command.queue.restart', function () {
            return new RestartCommand;
        });

        $this->commands('command.queue.restart');
    }

    /**
     * Register the connectors on the queue manager.
     * 注册消息队列中控制器的连接器
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        foreach (['Null', 'Sync', 'Database', 'Beanstalkd', 'Redis', 'Sqs'] as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }

    /**
     * Register the Null queue connector.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerNullConnector($manager)
    {
        $manager->addConnector('null', function () {
            return new NullConnector;
        });
    }

    /**
     * Register the Sync queue connector.
     * 注册同步消息队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSyncConnector($manager)
    {
        $manager->addConnector('sync', function () {
            return new SyncConnector;
        });
    }

    /**
     * Register the Beanstalkd queue connector.
     * 注册Beanstalkd消息队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerBeanstalkdConnector($manager)
    {
        $manager->addConnector('beanstalkd', function () {
            return new BeanstalkdConnector;
        });
    }

    /**
     * Register the database queue connector.
     * 注册数据库消息队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerDatabaseConnector($manager)
    {
        $manager->addConnector('database', function () {
            return new DatabaseConnector($this->app['db']);
        });
    }

    /**
     * Register the Redis queue connector.
     * 注册redis消息队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerRedisConnector($manager)
    {
        $app = $this->app;

        $manager->addConnector('redis', function () use ($app) {
            return new RedisConnector($app['redis']);
        });
    }

    /**
     * Register the Amazon SQS queue connector.
     * 注册SQL消息队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSqsConnector($manager)
    {
        $manager->addConnector('sqs', function () {
            return new SqsConnector;
        });
    }

    /**
     * Register the failed job services.
     * 注册失败任务的处理服务
     * @return void
     */
    protected function registerFailedJobServices()
    {
        $this->app->singleton('queue.failer', function ($app) {
            //config/queue.php failed配置选项
            $config = $app['config']['queue.failed'];

            //如果配置了这个table 那么则会初始化一个处理服务 否则 不管。
            if (isset($config['table'])) {
                return new DatabaseFailedJobProvider($app['db'], $config['database'], $config['table']);
            } else {
                return new NullFailedJobProvider;
            }
        });
    }

    /**
     * Register the Illuminate queued closure job.
     *
     * @return void
     */
    protected function registerQueueClosure()
    {
        $this->app->singleton('IlluminateQueueClosure', function ($app) {
            return new IlluminateQueueClosure($app['encrypter']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'queue', 'queue.worker', 'queue.listener', 'queue.failer',
            'command.queue.work', 'command.queue.listen',
            'command.queue.restart', 'queue.connection',
        ];
    }
}
