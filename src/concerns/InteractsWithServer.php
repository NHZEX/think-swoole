<?php

namespace think\swoole\concerns;

use Exception;
use Swoole\Process;
use Swoole\Runtime;
use Swoole\Server;
use Swoole\Server\Task;
use think\App;
use think\console\Output;
use think\Event;
use think\exception\Handle;
use think\helper\Str;
use think\swoole\FileWatcher;
use think\swoole\Job;
use Throwable;

/**
 * Trait InteractsWithServer
 * @package think\swoole\concerns
 * @property App    $container
 * @property Output $consoleOutput
 */
trait InteractsWithServer
{

    /**
     * 启动服务
     */
    public function run(): void
    {
        $this->getServer()->set(
            [
                'task_enable_coroutine' => true,
                'send_yield'            => true,
                'reload_async'          => true,
                'enable_coroutine'      => true,
            ] + $this->getConfig('server.options')
        );
        $this->initialize();
        $this->triggerEvent('init');

        //热更新
        if ($this->getConfig('hot_update.enable', false)) {
            $this->addHotUpdateProcess();
        }

        $this->getServer()->start();
    }

    /**
     * 停止服务
     */
    public function stop(): void
    {
        $this->getServer()->shutdown();
    }

    /**
     * "onStart" listener.
     */
    public function onStart()
    {
        $this->setProcessName('master');

        $this->triggerEvent("start", func_get_args());
    }

    /**
     * The listener of "managerStart" event.
     *
     * @return void
     */
    public function onManagerStart()
    {
        $this->setProcessName('manager');
        $this->triggerEvent("managerStart", func_get_args());
    }

    /**
     * "onWorkerStart" listener.
     *
     * @param \Swoole\Http\Server|mixed $server
     *
     * @throws Exception
     */
    public function onWorkerStart($server)
    {
        $this->resumeCoordinator('workerStart', function () use ($server) {
            Runtime::enableCoroutine(
                $this->getConfig('coroutine.enable', true),
                $this->getConfig('coroutine.flags', SWOOLE_HOOK_ALL)
            );

            if ($this->getConfig('options.clear_cache', false)) {
                $this->clearCache();
            }

            $this->setProcessName(($server->taskworker ? 'task' : 'worker') . "#{$server->worker_id}");

            $this->prepareApplication();

            $this->triggerEvent("workerStart", $this->app);
        });
    }

    /**
     * Set onTask listener.
     *
     * @param mixed $server
     * @param Task  $task
     */
    public function onTask($server, Task $task)
    {
        $this->runInSandbox(function (Event $event, App $app) use ($task) {
            if ($task->data instanceof Job) {
                if (is_array($task->data->name)) {
                    [$class, $method] = $task->data->name;
                    $object = $app->invokeClass($class, $task->data->params);
                    $object->{$method}();
                } else {
                    $app->invoke($task->data->name, $task->data->params);
                }
            } else {
                $event->trigger('swoole.task', $task);
            }
        }, $task->id);
    }

    /**
     * Set onShutdown listener.
     */
    public function onShutdown()
    {
        $this->triggerEvent('shutdown');
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->container->make(Server::class);
    }

    /**
     * Set swoole server listeners.
     */
    protected function setSwooleServerListeners()
    {
        foreach ($this->events as $event) {
            $listener = Str::camel("on_$event");
            $callback = method_exists($this, $listener) ? [$this, $listener] : function () use ($event) {
                $this->triggerEvent($event, func_get_args());
            };

            $this->getServer()->on($event, $callback);
        }
    }

    /**
     * 热更新
     */
    protected function addHotUpdateProcess()
    {
        $process = new Process(function () {
            $watcher = new FileWatcher(
                $this->getConfig('hot_update.include', []),
                $this->getConfig('hot_update.exclude', []),
                $this->getConfig('hot_update.name', [])
            );

            $watcher->watch(function ($path) {
                $this->consoleOutput->info("[FW] $path <comment>reload</comment>");
                $this->getServer()->reload();
            });
        }, false, 0, true);

        $this->addProcess($process);
    }

    /**
     * Add process to http server
     *
     * @param Process $process
     */
    public function addProcess(Process $process): void
    {
        $this->getServer()->addProcess($process);
    }

    /**
     * 清除apc、op缓存
     */
    protected function clearCache()
    {
        if (extension_loaded('apc')) {
            apc_clear_cache();
        }

        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }

    /**
     * Set process name.
     *
     * @param $process
     */
    protected function setProcessName($process)
    {
        // Mac OSX不支持进程重命名
        if (stristr(PHP_OS, 'DAR')) {
            return;
        }

        $appName = $this->container->config->get('app.name', 'ThinkPHP');

        $name = sprintf('%s: %s', $appName, $process);

        swoole_set_process_name($name);
    }

    /**
     * Log server error.
     *
     * @param Throwable|Exception $e
     */
    public function logServerError(Throwable $e)
    {
        /** @var Handle $handle */
        $handle = $this->container->make(Handle::class);

        $handle->renderForConsole(new Output(), $e);

        $handle->report($e);
    }
}
