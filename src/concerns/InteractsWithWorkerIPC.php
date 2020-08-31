<?php
declare(strict_types=1);

namespace think\swoole\concerns;

use Swoole\Atomic;
use Swoole\Server;
use think\App;
use think\swoole\IpcResponse;
use function str_starts_with;
use function unserialize;

/**
 * Trait InteractsWithWorkerIpc
 * @package think\swoole\concerns
 * @property App $container
 * @method Server getServer()
 */
trait InteractsWithWorkerIPC
{
    /** @var IpcResponse[] */
    protected $waitResponse = [];

    /** @var Atomic\Long */
    protected $ipcAutoIncrement;

    public function prepareWorkerIPC()
    {
        $this->ipcAutoIncrement = new Atomic\Long(0);
    }

    /**
     * @return Atomic
     */
    public function getIpcAutoIncrement(): Atomic\Long
    {
        return $this->ipcAutoIncrement;
    }

    /**
     * @param $rid
     * @param $dstWorkerId
     * @param $message
     * @return bool
     */
    public function sendIpcResponse($rid, $dstWorkerId, $message)
    {
        return $this->getServer()->sendMessage(IpcResponse::pakc($rid, $message), $dstWorkerId);
    }

    public function addIpcListen(IpcResponse $response)
    {
        $this->waitResponse[$response->getRid()] = $response;
    }

    public function removeIpcListen(IpcResponse $response)
    {
        unset($this->waitResponse[$response->getRid()]);
    }

    public function onPipeMessage($server, $srcWorkerId, $message)
    {
        if (str_starts_with($message, 'a:')) {
            $pack = @unserialize($message);
            if (is_array($pack) && isset($pack['rid'])) {
                if (isset($this->waitResponse[$pack['rid']])) {
                    $resp = $this->waitResponse[$pack['rid']];
                    $this->removeIpcListen($resp);
                    $resp->resume($srcWorkerId, $pack['msg']);
                    return;
                }
            }
        }

        $this->triggerEvent("pipeMessage", [
            $server, $srcWorkerId, $message
        ]);
    }
}
