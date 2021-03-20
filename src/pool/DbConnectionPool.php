<?php
declare(strict_types=1);

namespace think\swoole\pool;

use Smf\ConnectionPool\Connectors\ConnectorInterface;
use think\db\ConnectionInterface;
use function call_user_func;

class DbConnectionPool implements ConnectorInterface
{
    /** @var callable  */
    protected $creator;

    public function __construct(callable $creator)
    {
        $this->creator = $creator;
    }

    public function connect(array $config)
    {
        return call_user_func($this->creator);
    }

    /**
     * @param ConnectionInterface $connection
     * @return mixed|void
     */
    public function disconnect($connection)
    {
        $connection->close();
    }

    public function isConnected($connection): bool
    {
        return true;
    }

    public function reset($connection, array $config)
    {
    }

    public function validate($connection): bool
    {
        return $connection instanceof ConnectionInterface;
    }
}
