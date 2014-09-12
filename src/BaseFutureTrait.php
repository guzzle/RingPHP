<?php
namespace GuzzleHttp\Ring;

/**
 * Implements common future functionality.
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
}
