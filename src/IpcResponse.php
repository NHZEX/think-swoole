<?php
declare(strict_types=1);

namespace think\swoole;

use Swoole\Coroutine;
use function serialize;

class IpcResponse
{
    protected $rid;

    protected $cid;

    protected $fromWorkerId;

    protected $message;

    public static function pakc($rid, $message): string
    {
        return serialize(['rid' => $rid, 'msg' => $message]);
    }

    public function __construct()
    {
        $this->rid = Manager::getInstance()
            ->getIpcAutoIncrement()
            ->add(1);
        $this->cid = Coroutine::getCid();
    }

    public function yield(): void
    {
        Coroutine::yield();
    }

    public function resume(int $fromWorkerId, $message): void
    {
        $this->fromWorkerId = $fromWorkerId;
        $this->message = $message;
        Coroutine::resume($this->cid);
    }

    /**
     * @return int|string
     */
    public function getRid()
    {
        return $this->rid;
    }

    /**
     * @return int
     */
    public function getCid(): int
    {
        return $this->cid;
    }

    /**
     * @return int
     */
    public function getFromWorkerId()
    {
        return $this->fromWorkerId;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }
}
