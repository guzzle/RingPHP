<?php
namespace GuzzleHttp\Ring;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;

/**
 * Implements common future functionality.
 *
 * Note: you must implement processResult in order to use this trait.
 *
 * @property mixed $result Actual data used by the future. Accessing this
 *     property will cause the future to block if needed.
 */
trait BaseFutureTrait
{
    /** @var callable|null Dereference function */
    private $dereffn;

    /** @var callable|null Cancel function */
    private $cancelfn;

    /** @var bool */
    private $isCancelled = false;

    /**
     * @param callable $deref  Function that blocks until the response is complete.
     *                         This function MUST return a value that can be
     *                         understood by the future, or an exception to
     *                         raise when the future is dereferenced.
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

    public function realized()
    {
        return $this->dereffn === null && !$this->isCancelled;
    }

    public function cancel()
    {
        // Cannot cancel a cancelled or completed future.
        if (!$this->dereffn && !$this->cancelfn) {
            return false;
        }

        // If this is here, the it hasn't realized. Remove the function and
        // provide a data variable to prevent it from dereferencing.
        $this->dereffn = null;
        $this->isCancelled = true;

        // unset the data so that subsequent access will fail because it is
        // cancelled.
        unset($this->data);

        // if no cancel function is provided, then we cannot truly cancel.
        if (!$this->cancelfn) {
            return false;
        }

        // Return the result of invoking the cancel function.
        $cancelfn = $this->cancelfn;
        $this->cancelfn = null;

        return $cancelfn($this);
    }

    public function cancelled()
    {
        return $this->isCancelled;
    }

    public function deref()
    {
        return $this->result;
    }

    /**
     * This function handles retrieving the dereferenced result when requested.
     *
     * @param string $name Should always be "data" or an exception is thrown.
     *
     * @return mixed Returns the dereferenced data.
     * @throws \RuntimeException
     * @throws CancelledFutureAccessException
     */
    public function __get($name)
    {
        if ($name !== 'result') {
            throw new \RuntimeException("Class has no {$name} property");
        } elseif ($this->isCancelled) {
            throw new CancelledFutureAccessException('You are attempting '
                . 'to access a future that has been cancelled.');
        }

        $deref = $this->dereffn;
        // Unset these as they are no longer needed.
        $this->dereffn = $this->cancelfn = null;

        return $this->result = $this->processResult($deref());
    }

    /**
     * Function that processes the dereferenced result value. This function
     * must be implemented when using this trait.
     *
     * @param mixed $result Result to process
     *
     * @return mixed Returns the result value.
     * @throws \Exception on error.
     */
    abstract protected function processResult($result);
}
