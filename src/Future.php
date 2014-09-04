<?php
namespace GuzzleHttp\Ring;

/**
 * Represents a future response that can be used just like a normal response.
 *
 * When the response hash is used, the future of the response is dereferenced
 * and used as the internal response data.
 *
 * @property array $data
 */
class Future implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var callable */
    private $deref;

    /**
     * @param callable $deref Function that blocks until the response is complete
     */
    public function __construct(callable $deref)
    {
        $this->deref = $deref;
    }

    /**
     * Returns the future response as a regular array. Blocks until finished.
     *
     * @return array
     */
    public function deref()
    {
        return $this->data;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function count()
    {
        return count($this->data);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /** @internal */
    public function __get($name)
    {
        if ($name == 'data') {
            $this->data = call_user_func($this->deref);
        }

        return $this->data;
    }
}
