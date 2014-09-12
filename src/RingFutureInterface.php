<?php
namespace GuzzleHttp\Ring;

/**
 * Represents a future response that can be used just like a normal response.
 *
 * When the response is accessed as a normal response array, it MUST block
 * until the requested value is available. Use the other FutureInterface
 * functions like realized() and cancelled() to ensure you will not block when
 * accessing the future response.
 */
interface RingFutureInterface extends
    FutureInterface,
    \ArrayAccess,
    \Countable,
    \IteratorAggregate {};
