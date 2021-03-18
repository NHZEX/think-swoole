<?php

namespace think\swoole;

use RuntimeException;
use think\swoole\coroutine\Context;

class App extends \think\App
{
    protected static $lockInstance = false;

    protected $allowClearInstances = false;

    public static function setInstance($instance): void
    {
        if (self::$lockInstance) {
            throw new RuntimeException('Unexpected container instance change');
        }
        parent::setInstance($instance);
    }

    public static function lockInstance(bool $lock = true): void
    {
        self::$lockInstance = $lock;
    }

    public function runningInConsole()
    {
        return Context::hasData('_fd');
    }

    public function allowClearInstances(bool $allow = true): void
    {
        $this->allowClearInstances = $allow;
    }

    public function clearInstances()
    {
        if (!$this->allowClearInstances) {
            throw new RuntimeException('Unexpected container context clear');
        }
        $this->instances = [];
    }
}
