<?php
namespace GuzzleHttp\Ring\Future;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;
use GuzzleHttp\Ring\Exception\RingException;
use React\Promise\PromiseInterface;

/**
 * Implements common future functionality built on top of promises.
 */
trait BaseFutureTrait
{
    /** @var callable */
    private $dereffn;

    /** @var callable */
    private $cancelfn;

    /** @var PromiseInterface */
    private $promise;

    /** @var \Exception */
    private $error;
    private $result;

    private $isShadowed = false;
    private $isRealized = false;
    private $isCancelled = false;

    /**
     * @param PromiseInterface $promise Promise to shadow with the future. Only
     *                                  supply if the promise is not owned
     *                                  by the deferred value.
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
    }

    public function deref()
    {
        if (!$this->isRealized) {
            $this->addShadow();
            if (!$this->isRealized && $this->dereffn) {
                $this->invokeDeref();
            }
            if (!$this->isRealized) {
                $this->error = new RingException('Deref did not resolve future');
            }
        }

        if ($this->error) {
            throw $this->error;
        }

        return $this->result;
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null,
        callable $onProgress = null
    ) {
        return $this->promise->then($onFulfilled, $onRejected, $onProgress);
    }

    public function cancelled()
    {
        if (!$this->isRealized) {
            $this->addShadow();
        }

        return $this->isCancelled;
    }

    public function realized()
    {
        if (!$this->isRealized) {
            $this->addShadow();
        }

        return $this->isRealized;
    }

    public function cancel()
    {
        // Cannot cancel a cancelled or completed future.
        if ($this->isRealized) {
            return false;
        }

        $cancelfn = $this->cancelfn;
        $this->markCancelled(new CancelledFutureAccessException());

        return $cancelfn ? $cancelfn($this) : false;
    }

    /**
     * Adds a then() shadow to the promise to get the resolved value or error.
     */
    private function addShadow()
    {
        if ($this->isShadowed) {
            return;
        }

        $this->isShadowed = true;
        // Get the result and error when the promise is resolved. Note that
        // calling this function might trigger the resolution immediately.
        $this->promise->then(
            function ($value) {
                $this->isRealized = true;
                $this->result = $value;
                $this->dereffn = $this->cancelfn = null;
            },
            function ($error) {
                $this->isRealized = true;
                $this->error = $error;
                $this->dereffn = $this->cancelfn = null;
                if ($error instanceof CancelledFutureAccessException) {
                    $this->markCancelled($error);
                }
            }
        );
    }

    /**
     * Invoked the dereference function and handles the various outcomes.
     */
    private function invokeDeref()
    {
        try {
            $deref = $this->dereffn;
            $this->dereffn = null;
            $result = $deref();
            // The deref function can return a value to resolve.
            if ($result !== null) {
                $this->isRealized = true;
                if ($result === $this) {
                    $this->error = new \LogicException('Cannot resolve to itself');
                } else {
                    $this->result = $result;
                }
            }
        } catch (CancelledFutureAccessException $e) {
            // Throwing this exception adds an error and marks the
            // future as cancelled.
            $this->markCancelled($e);
        } catch (\Exception $e) {
            // Defer can throw to reject.
            $this->error = $e;
            $this->isRealized = true;
        }
    }

    /**
     * Marks the future as cancelled with the provided exception.
     *
     * @param CancelledFutureAccessException $e
     */
    private function markCancelled(CancelledFutureAccessException $e)
    {
        $this->dereffn = $this->cancelfn = null;
        $this->isCancelled = $this->isRealized = true;
        $this->error = $e;
    }
}
