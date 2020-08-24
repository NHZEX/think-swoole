<?php
declare(strict_types=1);

namespace think\swoole\concerns;

use Swoole\Server;
use think\App;
use think\swoole\contract\ProcessAbstract;
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

            /** @var ProcessAbstract $process */
            $process->init($this);
            $this->process[get_class($process)] = $process;
        }
    }

    public function findProcess(string $className): ProcessAbstract
    {
        return $this->process[$className];
    }
}
