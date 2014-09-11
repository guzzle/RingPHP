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
 * Futures can be cancelled if they have not already been derefereced.
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
 *                       already dereferenced the future.
 */
class Future implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var callable|null Dereference function */
    private $dereffn;

    /** @var callable|null Cancel function */
    private $cancelfn;

    /** @var bool */
    private $isCancelled = false;

    /**
     * @param callable $deref  Function that blocks until the response is complete
     * @param callable $cancel If possible and reasonable, provide a function
     *                         that can be used to cancel the future from
     *                         sending. The cancel function accepts the
     *                         future object and returns true on a successful
     *                         cancel and false on a failed cancel.
     */
    public function __construct(callable $deref, callable $cancel = null)
    {
        $this->dereffn = $deref;
        $this->cancelfn = $cancel;
    }

    /**
     * Returns the future response as a regular response array.
     *
     * This method must block until the future has a result or is cancelled.
     *
     * @return array
     */
    public function deref()
    {
        // Return the data if available, or call __get() to dereference.
        return $this->data;
    }

    /**
     * Returns true if the future has been dereferenced.
     *
     * @return bool
     */
    public function dereferenced()
    {
        return $this->dereffn === null && !$this->isCancelled;
    }

    /**
     * Cancels the future response from sending a request when dereferenced.
     *
     * If the future is already done or cancelled, return false. If the future
     * has not been dereferenced, then the dereference function will be
     * disassociated from the future, will not be called, and the future state
     * will be changed to cancelled. If the future has a cancel function, then
     * it will be invoked and the invocation result is returned.
     *
     * @return bool
     */
    public function cancel()
    {
        // Cannot cancel a cancelled or completed future.
        if (!$this->dereffn && !$this->cancelfn) {
            return false;
        }

        // If this is here, the it hasn't dereferenced. Remove the function and
        // provide a data variable to prevent it from dereferencing.
        $this->dereffn = null;
        $this->data = [];
        $this->isCancelled = true;

        // if no cancel function is provided, then we cannot truly cancel.
        if (!$this->cancelfn) {
            return false;
        }

        // Return the result of invoking the cancel function.
        $cancelfn = $this->cancelfn;
        $this->cancelfn = null;

        return $cancelfn($this);
    }

    /**
     * Returns true if the future was cancelled.
     *
     * @return bool
     */
    public function cancelled()
    {
        return $this->isCancelled;
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
            $deref = $this->dereffn;
            $this->dereffn = $this->cancelfn = null;
            return $this->data = $deref();
        }

        throw new \RuntimeException("Class has no $name property");
    }
}
