<?php
namespace GuzzleHttp\Ring;

/**
 * Represents a simple future value that responds to deref to retrieve a value.
 */
class FutureValue implements FutureInterface
{
    use MagicFutureTrait;

    protected function processResult($result)
    {
        return $result;
    }
}
