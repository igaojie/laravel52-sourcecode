<?php

namespace Illuminate\Queue\Failed;

use Carbon\Carbon;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * The connection resolver implementation.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The database connection name.
     *
     * @var string
     */
    protected $database;

    /**
     * The database table.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database failed job provider.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $database
     * @param  string  $table
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver, $database, $table)
    {
        $this->table = $table;
        $this->resolver = $resolver;
        $this->database = $database;
    }

    /**
     * Log a failed job into storage.
     * 记录失败的任务到存储
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @return int|null
     */
    public function log($connection, $queue, $payload)
    {
        $failed_at = Carbon::now();

        return $this->getTable()->insertGetId(compact('connection', 'queue', 'payload', 'failed_at'));
    }

    /**
     * Get a list of all of the failed jobs.
     * 获取所有的失败任务的列表按照id倒叙排列
     * @return array
     */
    public function all()
    {
        return $this->getTable()->orderBy('id', 'desc')->get();
    }

    /**
     * Get a single failed job.
     * 获取单个失败的任务
     * @param  mixed  $id
     * @return array
     */
    public function find($id)
    {
        return $this->getTable()->find($id);
    }

    /**
     * Delete a single failed job from storage.
     * 从存储介质里删掉一个失败的任务
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        return $this->getTable()->where('id', $id)->delete() > 0;
    }

    /**
     * Flush all of the failed jobs from storage.
     * 清空存储介质里所有的失败任务
     * @return void
     */
    public function flush()
    {
        $this->getTable()->delete();
    }

    /**
     * Get a new query builder instance for the table.
     * 获取一个表对象实例
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        return $this->resolver->connection($this->database)->table($this->table);
    }
}
