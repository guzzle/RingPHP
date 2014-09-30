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

    /** @var bool */
    private $realizedAtom;

    /**
     * @param callable $deref    Function that blocks until the response is
     *                           complete. This function MUST return a value
     *                           that can be understood by the future, or an
     *                           exception to raise when dereferenced.
     * @param callable $cancel   If possible and reasonable, provide a function
     *                           that can be used to cancel the future from
     *                           sending. The cancel function accepts the
     *                           future object and returns true on a successful
     *                           cancel and false on a failed cancel.
     * @param bool $realizedAtom An optional variable, passed by reference,
     *                           that can be updated to true/false to mark the
     *                           future as having been realized.
     */
    public function __construct(
        callable $deref,
        callable $cancel = null,
        &$realizedAtom = false
    ) {
        $this->dereffn = $deref;
        $this->cancelfn = $cancel;
        $this->realizedAtom =& $realizedAtom;
    }

    public function realized()
    {
        return (bool) $this->realizedAtom
            || ($this->dereffn === null && !$this->isCancelled);
    }

    public function cancel()
    {
        // Cannot cancel a cancelled or completed future.
        if ($this->realized()) {
            return false;
        }

        // If this is here, the it hasn't realized. Remove the function and
        // provide a data variable to prevent it from dereferencing.
        $this->realizedAtom = $this->dereffn = null;
        $this->isCancelled = true;
        $cancelfn = $this->cancelfn;
        $this->cancelfn = null;

        // if no cancel function is provided, then we cannot truly cancel.
        return !$cancelfn ? false : $cancelfn($this);
    }

    public function cancelled()
    {
        return $this->isCancelled;
    }
}
