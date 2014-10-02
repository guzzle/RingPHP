<?php
namespace GuzzleHttp\Ring;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;

/**
 * Implements common future functionality that is triggered when the result
 * property is accessed via a magic __get method.
 *
 * Note: you must implement processResult in order to use this trait.
 *
 * @property mixed $_value Actual data used by the future. Accessing this
 *     property will cause the future to block if needed.
 */
trait MagicFutureTrait
{
    use BaseFutureTrait;

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
        if ($name !== '_value') {
            throw new \RuntimeException("Class has no {$name} property");
        }

        return $this->_value = $this->deref();
    }
}
