<?php
declare(strict_types=1);

namespace think\swoole\concerns;

use RuntimeException;
use think\App;
use think\swoole\GlobalEvent;
use function class_exists;
use function get_class;

/**
 * Trait InteractsWithGlobalEvent
 * @package think\swoole\concerns
 * @property App $container
 * @property App $app
 */
trait InteractsWithGlobalEvent
{
    protected function prepareEventRegister(): void
    {
        $eventClass = $this->getConfig('event');
        if ($eventClass && class_exists($eventClass)) {
            $event = $this->container->invokeClass($eventClass);
            if (!($event instanceof GlobalEvent)) {
                throw new RuntimeException('event class error');
            }
            $this->container->bind(get_class($event), GlobalEvent::class);
            $this->container->instance(GlobalEvent::class, $event);
            $event->subscribe($this->container->event);
        }
    }

    public function getGlobalEvent(): GlobalEvent
    {
        return $this->container->make(GlobalEvent::class);
    }
}
