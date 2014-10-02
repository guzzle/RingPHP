<?php
namespace GuzzleHttp\Ring;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;
use GuzzleHttp\Ring\Exception\RingException;
use React\Promise\PromiseInterface;

/**
 * Implements common future functionality built on top of promises.
 */
trait BaseFutureTrait
{
    private $promise;
    private $dereffn;
    private $cancelfn;
    private $error;
    private $result;
    private $isRealized = false;
    private $isCancelled = false;

    /**
     * @param PromiseInterface $promise Promise to shadow with the future.
     * @param callable         $deref   Function that blocks until the deferred
     *                                  computation has been resolved. This
     *                                  function MUST resolve the deferred value
     *                                  associated with the supplied promise.
     * @param callable         $cancel  If possible and reasonable, provide a
     *                                  function that can be used to cancel the
     *                                  future from completing. The cancel
     *                                  function should return true on success
     *                                  and false on failure.
     */
    public function __construct(
        PromiseInterface $promise,
        callable $deref = null,
        callable $cancel = null
    ) {
        $this->promise = $promise;
        $this->dereffn = $deref;
        $this->cancelfn = $cancel;

        // Get the result and error when the promise is resolved.
        $this->promise->then(
            function ($value) {
                $this->result = $value;
                $this->isRealized = true;
                $this->dereffn = $this->cancelfn = null;
            },
            function ($error) {
                $this->error = $error;
                $this->isRealized = true;
                $this->dereffn = $this->cancelfn = null;
            }
        );
    }

    public function deref()
    {
        if (!$this->isRealized) {
            if ($this->dereffn) {
                $deref = $this->dereffn;
                $this->dereffn = null;
                $deref();
            }
            if (!$this->isRealized) {
                throw new RingException('Deref did not realize future');
            }
        }

        if ($this->error) {
            throw $this->error;
        } else {
            return $this->result;
        }
    }

    public function realized()
    {
        return $this->isRealized;
    }

    public function cancelled()
    {
        return $this->isCancelled;
    }

    public function cancel()
    {
        // Cannot cancel a cancelled or completed future.
        if ($this->isRealized) {
            return false;
        }

        // If this is here, the it hasn't realized. Remove the function and
        // provide a data variable to prevent it from dereferencing.
        $this->dereffn = null;
        $this->isCancelled = $this->isRealized = true;
        $cancelfn = $this->cancelfn;
        $this->cancelfn = null;
        $this->error = new CancelledFutureAccessException();

        // if no cancel function is provided, then we cannot truly cancel.
        return !$cancelfn ? false : $cancelfn($this);
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null,
        callable $onProgress = null
    ) {
        return new static(
            $this->promise->then($onFulfilled, $onRejected, $onProgress),
            $this->dereffn,
            $this->cancelfn
        );
    }
}
