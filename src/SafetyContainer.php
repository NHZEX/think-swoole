<?php
declare(strict_types=1);

namespace think\swoole;

class SafetyContainer extends \think\App
{
    public static $lockInstance = false;

    public static function setInstance($instance): void
    {
        if (self::$lockInstance) {
            return;
        }
        static::$instance = $instance;
    }

    public static function lockInstance(bool $lock = true): void
    {
        self::$lockInstance = $lock;
    }
}
