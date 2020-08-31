<?php
declare(strict_types=1);

namespace think\swoole;

use think\Event;

abstract class GlobalEvent
{
    abstract public function subscribe(Event $event);
}
