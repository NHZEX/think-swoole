<?php
declare(strict_types=1);

namespace think\swoole\concerns;

use Swoole\Coroutine;
use Swoole\Runtime;
use Swoole\Server;
use think\App;
use think\swoole\contract\ProcessAbstract;
use think\swoole\coroutine\Context;
use think\swoole\Manager;
use function array_unique;
use function class_exists;
use function get_class;

/**
 * Trait InteractsWithProcess
 * @package think\swoole\concerns
 * @mixin Manager
 * @method Server getServer()
 * @property App $container
 */
trait InteractsWithProcess
{
    /** @var ProcessAbstract[] */
    protected $process = [];

    protected function prepareProcess()
    {
        foreach (array_unique($this->getConfig('process', [])) as $class) {
            if (!class_exists($class)) {
                continue;
            }
            if (!(($process = $this->container->invokeClass($class)) instanceof ProcessAbstract)) {
                continue;
            }
            /** @var Manager $manager */
            $manager = $this;
            /** @var ProcessAbstract $process */
            $process->init($manager);
            $this->process[get_class($process)] = $process;
        }
    }

    public function findProcess(string $className): ProcessAbstract
    {
        return $this->process[$className];
    }

    public function processStart(ProcessAbstract $process)
    {
        Runtime::enableCoroutine(
            $this->getConfig('coroutine.enable', true),
            $this->getConfig('coroutine.flags', SWOOLE_HOOK_ALL)
        );

        if ($this->getConfig('options.clear_cache', false)) {
            $this->clearCache();
        }

        if ($process->isEnableCoroutine()) {
            Context::setData('_fd', Coroutine::getCid());
        }

        $this->prepareApplication();
    }
}
