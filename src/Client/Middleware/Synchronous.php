<?php
namespace GuzzleHttp\Ring\Client\Middleware;

use GuzzleHttp\Ring\Core;

/**
 * Forces all responses to be synchronous.
 */
class Synchronous
{
    public static function wrap(callable $handler)
    {
        return function (array $request) use ($handler) {
            return Core::deref($handler($request));
        };
    }
}
