<?php

namespace Illuminate\Queue;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Redis\Database;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class RedisQueue extends Queue implements QueueContract
{
    /**
     * The Redis database instance.
     * Redis实例
     * @var \Illuminate\Redis\Database
     */
    protected $redis;

    /**
     * The connection name.
     * 连接名
     * @var string
     */
    protected $connection;

    /**
     * The name of the default queue.
     * 默认的队列名称
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
     * 过期时间
     * @var int|null
     */
    protected $expire = 60;

    /**
     * Create a new Redis queue instance.
     * 创建一个Redis 队列实例
     * @param  \Illuminate\Redis\Database  $redis
     * @param  string  $default
     * @param  string  $connection
     * @return void
     */
    public function __construct(Database $redis, $default = 'default', $connection = null)
    {
        $this->redis = $redis;
        $this->default = $default;
        $this->connection = $connection;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $this->getConnection()->rpush($this->getQueue($queue), $payload);

        return Arr::get(json_decode($payload, true), 'id');
    }

    /**
     * Push a new job onto the queue after a delay.
     * 将任务推送到延迟队列有序集合
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = $this->createPayload($job, $data);

        $delay = $this->getSeconds($delay);

        $this->getConnection()->zadd($this->getQueue($queue).':delayed', $this->getTime() + $delay, $payload);

        return Arr::get(json_decode($payload, true), 'id');
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param  string  $queue
     * @param  string  $payload
     * @param  int  $delay
     * @param  int  $attempts
     * @return void
     */
    public function release($queue, $payload, $delay, $attempts)
    {
        $payload = $this->setMeta($payload, 'attempts', $attempts);

        $this->getConnection()->zadd($this->getQueue($queue).':delayed', $this->getTime() + $delay, $payload);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $original = $queue ?: $this->default;

        //获取执行任务的队列
        $queue = $this->getQueue($queue);

        //如果设置了过期时间
        if (! is_null($this->expire)) {
            $this->migrateAllExpiredJobs($queue);
        }

        //从default里取数据
        $job = $this->getConnection()->lpop($queue);

        if (! is_null($job)) {
            //将取出来的数据放入reserved暂存
            $this->getConnection()->zadd($queue.':reserved', $this->getTime() + $this->expire, $job);

            //执行
            return new RedisJob($this->container, $this, $job, $original);
        }
    }

    /**
     * Delete a reserved job from the queue.
     * 从暂存的有序集合中删除掉一个任务
     * @param  string  $queue
     * @param  string  $job
     * @return void
     */
    public function deleteReserved($queue, $job)
    {
        $this->getConnection()->zrem($this->getQueue($queue).':reserved', $job);
    }

    /**
     * Migrate all of the waiting jobs in the queue.
     *
     * @param  string  $queue
     * @return void
     */
    protected function migrateAllExpiredJobs($queue)
    {
        $this->migrateExpiredJobs($queue.':delayed', $queue);

        $this->migrateExpiredJobs($queue.':reserved', $queue);
    }

    /**
     * Migrate the delayed jobs that are ready to the regular queue.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function migrateExpiredJobs($from, $to)
    {
        $options = ['cas' => true, 'watch' => $from, 'retry' => 10];

        $this->getConnection()->transaction($options, function ($transaction) use ($from, $to) {
            // First we need to get all of jobs that have expired based on the current time
            // so that we can push them onto the main queue. After we get them we simply
            // remove them from this "delay" queues. All of this within a transaction.
            $jobs = $this->getExpiredJobs(
                $transaction, $from, $time = $this->getTime()
            );

            // If we actually found any jobs, we will remove them from the old queue and we
            // will insert them onto the new (ready) "queue". This means they will stand
            // ready to be processed by the queue worker whenever their turn comes up.
            if (count($jobs) > 0) {
                $this->removeExpiredJobs($transaction, $from, $time);

                $this->pushExpiredJobsOntoNewQueue($transaction, $to, $jobs);
            }
        });
    }

    /**
     * Get the expired jobs from a given queue.
     *
     * @param  \Predis\Transaction\MultiExec  $transaction
     * @param  string  $from
     * @param  int  $time
     * @return array
     */
    protected function getExpiredJobs($transaction, $from, $time)
    {
        return $transaction->zrangebyscore($from, '-inf', $time);
    }

    /**
     * Remove the expired jobs from a given queue.
     *
     * @param  \Predis\Transaction\MultiExec  $transaction
     * @param  string  $from
     * @param  int  $time
     * @return void
     */
    protected function removeExpiredJobs($transaction, $from, $time)
    {
        $transaction->multi();

        $transaction->zremrangebyscore($from, '-inf', $time);
    }

    /**
     * Push all of the given jobs onto another queue.
     *
     * @param  \Predis\Transaction\MultiExec  $transaction
     * @param  string  $to
     * @param  array  $jobs
     * @return void
     */
    protected function pushExpiredJobsOntoNewQueue($transaction, $to, $jobs)
    {
        call_user_func_array([$transaction, 'rpush'], array_merge([$to], $jobs));
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        $payload = parent::createPayload($job, $data);

        $payload = $this->setMeta($payload, 'id', $this->getRandomId());

        return $this->setMeta($payload, 'attempts', 1);
    }

    /**
     * Get a random ID string.
     * 产生一个32个字符的字符串
     *
     * @return string
     */
    protected function getRandomId()
    {
        return Str::random(32);
    }

    /**
     * Get the queue or return the default.
     * 获取一个队列。
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return 'queues:'.($queue ?: $this->default);
    }

    /**
     * Get the connection for the queue.
     * 获取队列的连接句柄
     * @return \Predis\ClientInterface
     */
    protected function getConnection()
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Get the underlying Redis instance.
     *
     * @return \Illuminate\Redis\Database
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Get the expiration time in seconds.
     *
     * @return int|null
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Set the expiration time in seconds.
     *
     * @param  int|null  $seconds
     * @return void
     */
    public function setExpire($seconds)
    {
        $this->expire = $seconds;
    }
}
