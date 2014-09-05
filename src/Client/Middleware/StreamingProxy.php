<?php
namespace GuzzleHttp\Ring\Client\Middleware;

/**
 * Sends streaming requests to a streaming compatible adapter while sending all
 * other requests to a default adapter.
 *
 * This, for example, could be useful for taking advantage of the performance
 * benefits of curl while still supporting true streaming through the
 * StreamAdapter.
 */
class StreamingProxy
{
    /**
     * @param callable $default   Adapter used for non-streaming responses
     * @param callable $streaming Adapter used for streaming responses
     *
     * @return callable Returns the composed handler.
     */
    public static function wrap(
        callable $default,
        callable $streaming
    ) {
        return function (array $request) use ($default, $streaming) {
            return empty($request['client']['stream'])
                ? $default($request)
                : $streaming($request);
        };
    }
}
