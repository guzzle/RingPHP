<?php
namespace GuzzleHttp\Ring;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;

/**
 * Future ring response that may or may not have completed.
 *
 * Future responses act just like a normal response hash. It can be iterated,
 * counted, and you can access it using associative array style access. When
 * the future response is used like a normal response hash, the future of the
 * response is "realized" (meaning blocks until the request has completed), and
 * the actual response is then used as the internal response data.
 *
 * Futures can be cancelled if they have not already been realized.
 * Cancelling a future will prevent the future from executing the dereference
 * function and will execute the function provided to the future that actually
 * handles the cancellation (e.g., telling an event loop to stop sending a
 * request or to close a socket). If no cancel function is provided, then a
 * request cannot be cancelled. If a cancel function is provided, then it
 * should accept the future as an argument and return true if the future was
 *cs
 *
 * @property array $data Actual data used by the future. Accessing this
 *                       property will cause the future to block if it has not
 *                       already realized the future.
 */
class Future implements RingFutureInterface
{
    use BaseFutureTrait;

    /**
     * Returns the future response as a regular response array.
     * {@inheritdoc}
     */
    public function deref()
    {
        // Return the data if available, or call __get() to dereference.
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
        if ($name !== 'data') {
            throw new \RuntimeException("Class has no {$name} property");
        } elseif ($this->isCancelled) {
            throw new CancelledFutureAccessException('You are attempting '
                . 'to access a future that has been cancelled.');
        }

        $deref = $this->dereffn;
        $this->dereffn = $this->cancelfn = null;

        return $this->data = $deref();
    }
}
