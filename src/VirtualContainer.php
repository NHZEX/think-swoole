<?php
declare(strict_types=1);

namespace think\swoole;

use ArrayIterator;

/**
 * Class VirtualContainer
 * @package think\swoole
 */
class VirtualContainer extends App
{
    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        static::getInstance()->bind($name, $value);
    }

    /**
     * @param $name
     * @return object
     */
    public function __get($name)
    {
        return static::getInstance()->get($name);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return static::getInstance()->exists($name);
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        static::getInstance()->delete($name);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return static::getInstance()->exists($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return static::getInstance()->make($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        static::getInstance()->bind($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        static::getInstance()->delete($offset);
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        $co = self::getInstance();
        return new ArrayIterator(self::getProtectionValue($co, 'instances'));
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        $co = self::getInstance();
        return count(self::getProtectionValue($co, 'instances'));
    }

    /**
     * @param object $obj
     * @param string $property
     * @return mixed
     */
    public static function getProtectionValue(object $obj, string $property)
    {
        $get = function () use ($property) {
            return $this->{$property};
        };
        return $get->call($obj);
    }
}
