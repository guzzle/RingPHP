<?php
namespace GuzzleHttp\Ring;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;

/**
 * Represents a simple future value that responds to deref to retrieve a value.
 */
class FutureValue implements FutureInterface
{
    use BaseFutureTrait;

    private $cache;

    public function deref()
    {
        if (!isset($this->cache)) {
            if ($this->isCancelled) {
                throw new CancelledFutureAccessException('You are attempting '
                    . 'to access a future that has been cancelled.');
            }
            $deref = $this->dereffn;
            $this->dereffn = $this->cancelfn = null;
            $this->cache = $deref();
        }

        return $this->cache;
    }
}
