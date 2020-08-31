<?php
declare(strict_types=1);

namespace think\swoole;

use RuntimeException;
use Swoole\Coroutine;
use function serialize;
use function spl_object_hash;

class IpcResponse
{
    protected $rid;

    protected $cid;

    protected $fromWorkerId;

    protected $message;

    private $cCheckStr;

    const HEAD = '__worker_ipc:';

    public static function pakc($rid, $message): string
    {
        return self::HEAD . serialize([$rid, $message]);
    }

    public function __construct()
    {
        $this->rid = Manager::getInstance()
            ->getIpcAutoIncrement()
            ->add(1);
        $this->cid = Coroutine::getCid();
        $this->generateCheck();
    }

    private function generateCheck()
    {
        Coroutine::getContext($this->cid)[static::class . spl_object_hash($this)] = true;
    }

    private function verify()
    {
        return (Coroutine::getContext($this->cid)[static::class . spl_object_hash($this)]) ?? false;
    }

    public function yield(): void
    {
        Coroutine::yield();
    }

    public function resume(int $fromWorkerId, $message): void
    {
        if (!$this->verify()) {
            throw new RuntimeException('resume coroutine context error');
        }
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
