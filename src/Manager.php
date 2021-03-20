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
use think\console\Output;
use think\swoole\concerns\InteractsWithCoordinator;
use think\swoole\concerns\InteractsWithGlobalEvent;
use think\swoole\concerns\InteractsWithHttp;
use think\swoole\concerns\InteractsWithPools;
use think\swoole\concerns\InteractsWithProcess;
use think\swoole\concerns\InteractsWithQueue;
use think\swoole\concerns\InteractsWithRpcServer;
use think\swoole\concerns\InteractsWithRpcClient;
use think\swoole\concerns\InteractsWithServer;
use think\swoole\concerns\InteractsWithSwooleTable;
use think\swoole\concerns\InteractsWithWebsocket;
use think\swoole\concerns\InteractsWithWorkerIPC;
use think\swoole\concerns\WithApplication;
use think\swoole\concerns\WithContainer;

/**
 * Class Manager
 */
class Manager
{
    use InteractsWithCoordinator,
        InteractsWithServer,
        InteractsWithGlobalEvent,
        InteractsWithWorkerIPC,
        InteractsWithSwooleTable,
        InteractsWithHttp,
        InteractsWithWebsocket,
        InteractsWithPools,
        InteractsWithProcess,
        InteractsWithRpcClient,
        InteractsWithRpcServer,
        InteractsWithQueue,
        WithContainer,
        WithApplication;

    protected static $managerInstance;

    /**
     * @var Output
     */
    protected $consoleOutput;

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

    protected function setManagerInstance()
    {
        if (self::$managerInstance !== null) {
            throw new RuntimeException('Repeat instance manager');
        }
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
        $this->prepareQueue();
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
