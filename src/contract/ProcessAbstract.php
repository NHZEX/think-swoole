<?php
declare(strict_types=1);

namespace think\swoole\contract;

use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Server;
use think\swoole\Manager;
use think\swoole\QuickHelper;
use function app;
use function sprintf;
use function stristr;

abstract class ProcessAbstract implements ProcessInterface
{
    use QuickHelper;

    /** @var bool */
    protected $enableCoroutine = true;

    protected $pipeType = SOCK_DGRAM;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @return static
     */
    final public static function getInstance(): self
    {
        return Manager::getInstance()->findProcess(static::class);
    }

    /**
     * @return Process|null
     */
    final public function getProcess(): ?Process
    {
        return $this->process;
    }

    /**
     * @return bool
     */
    final public function isEnableCoroutine(): bool
    {
        return $this->enableCoroutine;
    }

    final public function init(Manager $manager)
    {
        $this->manager = $manager;
        $this->server = $manager->getServer();
        $this->process = new Process(
            self::callWrap([$this, 'entrance']),
            false,
            $this->pipeType,
            $this->enableCoroutine
        );
        $manager->addProcess($this->process);
        return $this->process;
    }

    protected function entrance(): void
    {
        $this->manager->processStart($this);
        $this->setProcessName($this->processName());
        $this->worker();
    }

    abstract protected function worker();

    /**
     * @return string
     */
    public function workerName(): string
    {
        return static::class;
    }

    /**
     * @return string
     */
    protected function processName(): string
    {
        return "{$this->workerName()}#{$this->process->id}";
    }

    /**
     * Set process name.
     *
     * @param $name
     */
    protected function setProcessName($name)
    {
        // Mac OSX不支持进程重命名
        if (stristr(PHP_OS, 'DAR')) {
            return;
        }

        $appName = $appName = app()->config->get('app.name', 'ThinkPHP');

        $this->process->name(sprintf('%s: %s', $appName, $name));
    }

    /**
     * @param array|null $protocol
     * @required ext-swoole >= 4.5.3
     */
    protected function listenPipeMessage(array $protocol = null)
    {
        Coroutine::create(function () use ($protocol) {
            /** @var Coroutine\Socket $socket */
            $socket = $this->process->exportSocket();
            if (!empty($protocol)) {
                $socket->setProtocol($protocol);
            }
            while (true) {
                $result = $socket->recv();
                if (false === $result) {
                    $this->manager->getConsoleOutput()->writeln("{$this->processName()} pipe: [{$socket->errCode}] {$socket->errMsg}");
                    break;
                }
                if ('' === $result) {
                    $this->manager->getConsoleOutput()->writeln("{$this->processName()} pipe: down [empty recv]");
                    break;
                }
                Coroutine::create(self::callWrap([$this, 'handlePipeMessage']), $result);
            }
        });
    }

    abstract protected function handlePipeMessage(string $message);
}
