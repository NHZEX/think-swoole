<?php
declare(strict_types=1);

namespace think\swoole\concerns;

use Swoole\Atomic;
use Swoole\Server;
use think\App;
use think\swoole\IpcResponse;
use function call_user_func;
use function count;
use function str_starts_with;
use function strlen;
use function substr;
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
     * @param callable $call
     * @return false|mixed
     */
    public function sendIpcRequestFromCall(callable $call)
    {
        $resp = new IpcResponse();
        $this->addIpcListen($resp);
        $result = call_user_func($call, $resp);
        if ($result === false) {
            $this->removeIpcListen($resp);
            return null;
        }

        $resp->yield();
        return $resp->getMessage();
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
        if (str_starts_with($message, IpcResponse::HEAD)) {
            $pack = @unserialize(substr($message, strlen(IpcResponse::HEAD)));
            if (is_array($pack) && count($pack) === 2) {
                if ($resp = ($this->waitResponse[$pack[0]] ?? null)) {
                    $this->removeIpcListen($resp);
                    $resp->resume($srcWorkerId, $pack[1]);
                    return;
                }
            }
        }

        $this->triggerEvent("pipeMessage", [
            $server, $srcWorkerId, $message
        ]);
    }
}
