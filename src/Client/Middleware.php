<?php
namespace GuzzleHttp\Ring\Client;

use GuzzleHttp\Ring\Core;

/**
 * Provides basic middleware wrappers.
 *
 * If a middleware is more complex than a few lines of code, then it should
 * be implemented in a class rather than a static method.
 */
class Middleware
{
    /**
     * Sends future requests to a future compatible adapter while sending all
     * other requests to a default adapter.
     *
     * When the "future" option is not provided on a request, any future responses
     * are automatically converted to synchronous responses and block.
     *
     * @param callable $default Adapter used for non-streaming responses
     * @param callable $future  Adapter used for future responses
     *
     * @return callable Returns the composed handler.
     */
    public static function wrapFuture(
        callable $default,
        callable $future
    ) {
        return function (array $request) use ($default, $future) {
            return empty($request['client']['future'])
                ? Core::deref($default($request))
                : $future($request);
        };
    }

    /**
     * Sends streaming requests to a streaming compatible adapter while sending all
     * other requests to a default adapter.
     *
     * This, for example, could be useful for taking advantage of the performance
     * benefits of curl while still supporting true streaming through the
     * StreamAdapter.
     *
     * @param callable $default   Adapter used for non-streaming responses
     * @param callable $streaming Adapter used for streaming responses
     *
     * @return callable Returns the composed handler.
     */
    public static function wrapStreaming(
        callable $default,
        callable $streaming
    ) {
        return function (array $request) use ($default, $streaming) {
            return empty($request['client']['stream'])
                ? $default($request)
                : $streaming($request);
        };
    }

    /**
     * Forces all responses to be synchronous.
     *
     * @param callable $handler Handler to wrap
     *
     * @return callable Returns the composed handler.
     */
    public static function wrapSynchronous(callable $handler)
    {
        return function (array $request) use ($handler) {
            return Core::deref($handler($request));
        };
    }
}
