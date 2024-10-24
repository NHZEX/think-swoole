<?php

namespace think\swoole\concerns;

use Closure;
use Swoole\Server;
use think\App;
use think\swoole\App as SwooleApp;
use think\swoole\pool\Cache;
use think\swoole\pool\Db;
use think\swoole\Sandbox;
use think\swoole\VirtualContainer;
use Throwable;

/**
 * Trait WithApplication
 * @package think\swoole\concerns
 * @property App $container
 */
trait WithApplication
{
    use InteractsWithCoordinator;

    /**
     * @var SwooleApp
     */
    protected $app;

    /**
     * 获取配置
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $name, $default = null)
    {
        return $this->container->config->get("swoole.{$name}", $default);
    }

    /**
     * 触发事件
     * @param string $event
     * @param null $params
     */
    protected function triggerEvent(string $event, $params = null): void
    {
        $this->container->event->trigger("swoole.{$event}", $params);
    }

    /**
     * 监听事件
     * @param string $event
     * @param        $listener
     * @param bool $first
     */
    public function onEvent(string $event, $listener, bool $first = false): void
    {
        $this->container->event->listen("swoole.{$event}", $listener, $first);
    }

    protected function prepareApplication()
    {
        if (!$this->app instanceof SwooleApp) {
            $this->app = new VirtualContainer($this->container->getRootPath());
            $this->app->bind(SwooleApp::class, App::class);
            $this->app->bind(Server::class, $this->getServer());
            $this->app->bind("swoole.server", Server::class);
            //绑定连接池
            if ($this->getConfig('pool.db.enable', true)) {
                $this->app->bind('db', Db::class);
                $this->app->resolving(Db::class, function (Db $db) {
                    $db->setLog($this->container->log);
                });
            }
            if ($this->getConfig('pool.cache.enable', true)) {
                $this->app->bind('cache', Cache::class);
            }
            $this->app->initialize();
            $this->prepareConcretes();
        }
    }

    /**
     * 预加载
     */
    protected function prepareConcretes()
    {
        $defaultConcretes = ['db', 'cache', 'event'];

        $concretes = array_merge($defaultConcretes, $this->getConfig('concretes', []));

        foreach ($concretes as $concrete) {
            if ($this->app->has($concrete)) {
                $this->app->make($concrete);
            }
        }
    }

    /**
     * 获取沙箱
     * @return Sandbox
     */
    protected function getSandbox()
    {
        return $this->app->make(Sandbox::class);
    }

    /**
     * 在沙箱中执行
     * @param Closure $callable
     * @param null $fd
     * @param bool $persistent
     */
    protected function runInSandbox(Closure $callable, $fd = null, $persistent = false)
    {
        try {
            $this->getSandbox()->run($callable, $fd, $persistent);
        } catch (Throwable $e) {
            $this->logServerError($e);
        }
    }

}
