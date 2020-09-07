<?php

namespace think\swoole\pool;

use Closure;
use RuntimeException;
use Smf\ConnectionPool\ConnectionPool;
use Swoole\Coroutine;
use think\swoole\coroutine\Context;
use think\swoole\Pool;

abstract class Proxy
{
    const KEY_RELEASED = '__released';

    protected $pool;

    abstract protected function connectorClassName(): string;

    /**
     * Proxy constructor.
     * @param Closure $creator
     * @param array   $config
     */
    public function __construct(Closure $creator, array $config)
    {
        $classname = $this->connectorClassName();
        $this->pool = new ConnectionPool(Pool::pullPoolConfig($config), new $classname($creator), []);

        $this->pool->init();
    }

    protected function getPoolConnection()
    {
        return Context::rememberData("connection." . spl_object_id($this), function () {
            $connection = $this->pool->borrow();

            $connection->{static::KEY_RELEASED} = false;

            Coroutine::defer(function () use ($connection) {
                //自动归还
                $connection->{static::KEY_RELEASED} = true;
                $this->pool->return($connection);
            });

            return $connection;
        });
    }

    public function release()
    {
        $connection = $this->getPoolConnection();
        if ($connection->{static::KEY_RELEASED}) {
            return;
        }
        $this->pool->return($connection);
    }

    public function __call($method, $arguments)
    {
        $connection = $this->getPoolConnection();
        if ($connection->{static::KEY_RELEASED}) {
            throw new RuntimeException("Connection already has been released!");
        }

        return $connection->{$method}(...$arguments);
    }

}
