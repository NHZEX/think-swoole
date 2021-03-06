<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use RuntimeException;
use think\App;
use think\console\Output;
use think\swoole\concerns\InteractsWithGlobalEvent;
use think\swoole\concerns\InteractsWithHttp;
use think\swoole\concerns\InteractsWithPools;
use think\swoole\concerns\InteractsWithProcess;
use think\swoole\concerns\InteractsWithRpcServer;
use think\swoole\concerns\InteractsWithRpcClient;
use think\swoole\concerns\InteractsWithServer;
use think\swoole\concerns\InteractsWithSwooleTable;
use think\swoole\concerns\InteractsWithWebsocket;
use think\swoole\concerns\InteractsWithWorkerIPC;
use think\swoole\concerns\WithApplication;

/**
 * Class Manager
 */
class Manager
{
    use InteractsWithServer,
        InteractsWithGlobalEvent,
        InteractsWithWorkerIPC,
        InteractsWithSwooleTable,
        InteractsWithHttp,
        InteractsWithWebsocket,
        InteractsWithPools,
        InteractsWithProcess,
        InteractsWithRpcClient,
        InteractsWithRpcServer,
        WithApplication;

    protected static $managerInstance;

    /**
     * @var Output
     */
    protected $consoleOutput;

    /**
     * @var App
     */
    protected $container;

    /**
     * Server events.
     *
     * @var array
     */
    protected $events = [
        'start',
        'shutDown',
        'workerStart',
        'workerStop',
        'workerError',
        'workerExit',
        'packet',
        'task',
        'finish',
        'pipeMessage',
        'managerStart',
        'managerStop',
        'request',
    ];

    public static function getInstance():? self
    {
        return self::$managerInstance;
    }

    /**
     * Manager constructor.
     * @param App $container
     */
    public function __construct(App $container)
    {
        if (self::$managerInstance !== null) {
            throw new RuntimeException('Repeat instance manager');
        }
        $this->container = $container;
        self::$managerInstance = $this;
    }

    /**
     * Initialize.
     */
    protected function initialize(): void
    {
        $this->prepareEventRegister();
        $this->prepareWorkerIPC();
        $this->prepareTables();
        $this->preparePools();
        $this->prepareWebsocket();
        $this->setSwooleServerListeners();
        $this->prepareProcess();
        $this->prepareRpcServer();
        $this->prepareRpcClient();
    }

    /**
     * @return Output
     */
    public function getConsoleOutput():? Output
    {
        return $this->consoleOutput;
    }

    /**
     * @param Output $consoleOutput
     */
    public function setConsoleOutput(Output $consoleOutput): void
    {
        $this->consoleOutput = $consoleOutput;
    }
}
