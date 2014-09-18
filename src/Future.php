<?php
namespace GuzzleHttp\Ring;

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
 * successfully cancelled or false if it could not be cancelled.
 */
class Future implements RingFutureInterface
{
    use BaseFutureTrait;

    public function offsetExists($offset)
    {
        return isset($this->result[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->result[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->result[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->result[$offset]);
    }

    public function count()
    {
        return count($this->result);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->result);
    }

    protected function processResult($result)
    {
        if (!is_array($result)) {
            throw new \RuntimeException('The dereference function did not '
                . 'return an array. Found ' . Core::describeType($result));
        }

        return $result;
    }
}
