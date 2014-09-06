<?php
namespace GuzzleHttp\Ring;

/**
 * Represents a future response that can be used just like a normal response.
 *
 * Future acts just like a normal response hash. It can be iterated, counted,
 * and you can access it using associative array style access. When the future
 * response is used like a normal response hash, the future of the response is
 * "dereferenced" (meaning blocks until the request has completed), and the
 * actual response is then used as the internal response data.
 *
 * @property array $data Actual data used by the future. Accessing this
 *                       property will cause the future to block if it has not
 *                       already dereferenced the future.
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

    /**
     * Cancels the future response from sending a request when dereferenced.
     */
    public function cancel()
    {
        $this->data = [];
        $this->deref = null;
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
        if ($name === 'data') {
            $deref = $this->deref;
            return $this->data = $deref();
        }

        throw new \RuntimeException("Class has no $name property");
    }
}
