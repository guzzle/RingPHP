<?php
namespace GuzzleHttp\Ring\Future;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;

/**
 * Represents a future value that has been resolved or rejected.
 */
class CompletedFutureValue implements FutureInterface
{
    protected $result;
    protected $error;
    private $promise;

    /**
     * @param mixed      $result Resolved result
     * @param \Exception $e      Error. Pass a GuzzleHttp\Ring\Exception\CancelledFutureAccessException
     *                           to mark the future as cancelled.
     */
    public function __construct($result, \Exception $e = null)
    {
        $this->result = $result;
        $this->error = $e;
    }

    public function deref()
    {
        if ($this->error) {
            throw $this->error;
        }

        return $this->result;
    }

    public function realized()
    {
        return true;
    }

    public function cancel()
    {
        return false;
    }

    public function cancelled()
    {
        return $this->error instanceof CancelledFutureAccessException;
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null,
        callable $onProgress = null
    ) {
        if (!$this->promise) {
            $this->promise = $this->error
                ? new RejectedPromise($this->error)
                : new FulfilledPromise($this->result);
        }

        return $this->promise->then($onFulfilled, $onRejected, $onProgress);
    }
}
