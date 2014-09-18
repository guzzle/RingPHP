<?php
namespace GuzzleHttp\Ring;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;

/**
 * Implements common future functionality that is triggered when the result
 * property is accessed via a magic __get method.
 *
 * Note: you must implement processResult in order to use this trait.
 *
 * @property mixed $result Actual data used by the future. Accessing this
 *     property will cause the future to block if needed.
 */
trait MagicFutureTrait
{
    use BaseFutureTrait;

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
