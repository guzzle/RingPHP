<?php
namespace GuzzleHttp\Ring;

/**
 * Future that provides array-like access. When the array is used, the future
 * blocks until it has completed.
 */
interface ArrayFutureInterface extends
    FutureInterface,
    \ArrayAccess,
    \Countable,
    \IteratorAggregate {};
