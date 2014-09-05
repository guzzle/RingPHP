<?php
namespace GuzzleHttp\Ring;

use GuzzleHttp\Stream\StreamInterface;

/**
 * Provides core functionality of Ring adapters and middleware.
 */
class Core
{
    /**
     * Adds a "then" function to a request that is invoked when the request
     * completes.
     *
     * If an existing "then" function is present, then a new "then" will be
     * added to the request. The new "then" function will become an aggregate
     * of the previous then function that first calls the previous function
     * followed by the new function.
     *
     * The provided function accepts the returned response, and can optionally
     * return a new response which will override the response associated with
     * the request.
     *
     * @param array    $request Request to update
     * @param callable $fn      Function to invoke on completion.
     *
     * @return array|Future
     */
    public static function then(array $request, callable $fn)
    {
        if (isset($request['then'])) {
            $then = $request['then'];
            $fn = function ($response) use ($request, $fn, $then) {
                $result = $then($response) ?: $response;
                return $fn($result) ?: $result;
            };
        }

        $request['then'] = $fn;

        return $request;
    }

    /**
     * Derefs a response and returns future responses as an array.
     *
     * If the provided response is a normal response hash, it is returned.
     *
     * @param array|callable $response Response to dereference
     *
     * @return array
     */
    public static function deref($response)
    {
        return $response instanceof Future ? $response->deref() : $response;
    }

    /**
     * Gets an array of header line values for a specific header from a
     * message that contains a "headers" key.
     *
     * This method searches for a header using a case-insensitive search.
     *
     * @param array  $message Request or response hash.
     * @param string $header  Header to check
     *
     * @return array
     */
    public static function headerLines(array $message, $header)
    {
        // Slight optimization for exact matches.
        if (isset($message['headers'][$header])) {
            return $message['headers'][$header];
        }

        // Check for message with no headers after the "fast" isset check.
        if (!isset($message['headers'])) {
            return [];
        }

        // Iterate and case-insensitively find the header by name.
        foreach ($message['headers'] as $name => $value) {
            if (!strcasecmp($name, $header)) {
                return $value;
            }
        }

        return [];
    }

    /**
     * Gets a case-insensitive header as a string from a message that contains
     * a "headers" key.
     *
     * @param array  $message Request or response hash.
     * @param string $header  Header to check
     *
     * @return string|null Returns all matching header lines as a string.
     */
    public static function header(array $message, $header)
    {
        $match = self::headerLines($message, $header);
        return $match ? implode(', ', $match) : null;
    }

    /**
     * Returns the first header value from a message using case-insensitive
     * search.
     *
     * @param array  $message Request or response hash.
     * @param string $header  Header to check
     *
     * @return array
     */
    public static function firstHeader(array $message, $header)
    {
        $match = self::headerLines($message, $header);
        return isset($match[0]) ? $match[0] : null;
    }

    /**
     * Returns true if a message has the provided case-insensitive header.
     *
     * @param array  $message Request or response hash.
     * @param string $header  Header to check
     *
     * @return bool
     */
    public static function hasHeader(array $message, $header)
    {
        return (bool) self::headerLines($message, $header);
    }

    /**
     * Parses an array of header lines into an associative array of headers.
     *
     * @param array $lines Header lines
     *
     * @return array
     */
    public static function headersFromLines(array $lines)
    {
        $headers = [];

        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            $headers[trim($parts[0])][] = isset($parts[1])
                ? trim($parts[1])
                : null;
        }

        return $headers;
    }

    /**
     * Creates a URL from a request.
     *
     * If the "url" key is present on the request, it is returned.
     *
     * @param array $request Request to get the URL from
     *
     * @return string Returns a URL string
     */
    public static function url(array $request)
    {
        if (isset($request['url'])) {
            return $request['url'];
        }

        $uri = (isset($request['scheme'])
                ? $request['scheme'] : 'http') . '://';

        if ($host = self::header($request, 'host')) {
            $uri .= $host;
        }

        if (isset($request['uri'])) {
            $uri .= $request['uri'];
        }

        if (isset($request['query_string'])) {
            $uri .= '?' . $request['query_string'];
        }

        return $uri;
    }

    /**
     * Reads the body of a request or response hash into a string.
     *
     * @param array $message Message that contains a "body" key.
     *
     * @return null|string Returns the body as a string.
     * @throws \InvalidArgumentException if a request body is invalid.
     */
    public static function body(array $message)
    {
        if (!isset($message['body'])) {
            return null;
        }

        if ($message['body'] instanceof StreamInterface) {
            return (string) $message['body'];
        }

        switch (gettype($message['body'])) {
            case 'string':
                return $message['body'];
            case 'resource':
                return stream_get_contents($message['body']);
            case 'object':
                if ($message['body'] instanceof \Iterator) {
                    return implode('', iterator_to_array($message['body']));
                } elseif (method_exists($message['body'], '__toString')) {
                    return (string) $message['body'];
                }
            default:
                throw new \InvalidArgumentException('Invalid request body: '
                    . gettype($message['body']));
        }
    }
}
