<?php
namespace GuzzleHttp\Ring;

/**
 * Represents a future value that responds to deref to retrieve a value.
 *
 * You can retrieve a promise from the future that is delivered a value when
 * the future is resolved. Note that any modifications to promises created
 * from the future will not affect the value stored in the future when it is
 * dereferenced.
 */
class FutureValue implements FutureInterface
{
    use BaseFutureTrait;
}
