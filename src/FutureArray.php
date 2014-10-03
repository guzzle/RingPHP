<?php
namespace GuzzleHttp\Ring;

/**
 * Represents a future value that when dereferenced returns an array.
 *
 * This future value can be accessed like a regular array.
 */
class FutureArray implements ArrayFutureInterface
{
    use MagicFutureTrait;

    public function offsetExists($offset)
    {
        return isset($this->_value[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_value[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->_value[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_value[$offset]);
    }

    public function count()
    {
        return count($this->_value);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->_value);
    }
}
