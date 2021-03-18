<?php

namespace think\swoole;

use RuntimeException;
use think\swoole\coroutine\Context;

class App extends \think\App
{
    public static $lockInstance = false;

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

    public function clearInstances()
    {
        $this->instances = [];
    }
}
