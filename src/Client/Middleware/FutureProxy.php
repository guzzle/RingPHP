<?php
namespace GuzzleHttp\Ring\Client\Middleware;

use GuzzleHttp\Ring\Core;

/**
 * Sends future requests to a future compatible adapter while sending all
 * other requests to a default adapter.
 *
 * When the "future" option is not provided on a request, any future responses
 * are automatically converted to synchronous responses and block.
 */
class FutureProxy
{
    /**
     * @param callable $default Adapter used for non-streaming responses
     * @param callable $future  Adapter used for future responses
     *
     * @return callable Returns the composed handler.
     */
    public static function wrap(
        callable $default,
        callable $future
    ) {
        return function (array $request) use ($default, $future) {
            return empty($request['client']['future'])
                ? Core::deref($default($request))
                : $future($request);
        };
    }
}
