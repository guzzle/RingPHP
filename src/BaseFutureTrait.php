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
    /** @var callable */
    private $dereffn;

    /** @var callable */
    private $cancelfn;

    /** @var PromiseInterface */
    private $promise;
    private $error;
    private $result;
    private $isRealized = false;
    private $isCancelled = false;

    /**
     * @param PromiseInterface $promise Promise to shadow with the future.
     * @param callable        $deref    Function that blocks until the deferred
     *                                  computation has been resolved. This
     *                                  function MUST resolve the deferred value
     *                                  associated with the supplied promise.
     * @param callable        $cancel   If possible and reasonable, provide a
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
                $this->isRealized = true;
                $this->result = $value;
                $this->dereffn = $this->cancelfn = null;
            },
            function ($error) {
                $this->isRealized = true;
                $this->error = $error;
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
                try {
                    $deref();
                } catch (\Exception $e) {
                    $this->error = $e;
                    $this->isRealized = true;
                }
            }
            if (!$this->isRealized) {
                throw new RingException('Deref did not realize future');
            }
        }

        if ($this->error) {
            throw $this->error;
        }

        return $this->result;
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

        $cancelfn = $this->cancelfn;
        $this->dereffn = $this->cancelfn = null;
        $this->isCancelled = $this->isRealized = true;
        $this->error = new CancelledFutureAccessException();

        return $cancelfn ? $cancelfn($this) : false;
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null,
        callable $onProgress = null
    ) {
        return $this->promise->then($onFulfilled, $onRejected, $onProgress);
    }
}
