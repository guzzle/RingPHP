<?php
namespace GuzzleHttp\Ring;

use GuzzleHttp\Stream\StreamInterface;

/**
 * Provides core functionality of Ring adapters and middleware.
 */
class Core
{
    /**
     * Wraps a response with a future response that invokes the provided
     * callable when the response completes.
     *
     * If the response is not a future response, then the callable is
     * invoked immediately.
     *
     * @param mixed    $response
     * @param callable $onComplete A callable that is invoked when complete.
     *
     * @return array|FutureResponse
     */
    public static function then($response, callable $onComplete)
    {
        if ($response instanceof FutureResponse) {
            return self::future(function () use ($response, $onComplete) {
                $onComplete();
                return Core::deref($response);
            });
        } else {
            $onComplete();
            return $response;
        }
    }

    /**
     * Creates a future response using a callable as the dereferencing callable.
     *
     * @param callable $deref Callable that blocks until the response is complete
     *
     * @return FutureResponse
     */
    public static function future(callable $deref)
    {
        return new FutureResponse($deref);
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
        return $response instanceof FutureResponse
            ? $response->deref()
            : $response;
    }

    /**
     * Gets a header from a message that contains a "headers" key.
     *
     * This method searches for a header using a case-insensitive search.
     *
     * @param array  $message  Request or response hash.
     * @param string $header   Header to check
     * @param bool   $asString Set to true to ensure that multi-valued headers
     *                         are returned as a string.
     *
     * @return string|array|null
     */
    public static function header(array $message, $header, $asString = false)
    {
        if (!isset($message['headers'])) {
            return null;
        }

        $match = null;

        // Slight optimization for exact matches.
        if (isset($message['headers'][$header])) {
            $match = $message['headers'][$header];
        } else {
            foreach ($message['headers'] as $name => $value) {
                if (!strcasecmp($name, $header)) {
                    $match = $value;
                    break;
                }
            }
        }

        if ($match && $asString && is_array($match)) {
            return implode(', ', $match);
        }

        return $match;
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
            $l = self::parseHeaderLine($line);
            if (!isset($headers[$l[0]])) {
                $headers[$l[0]] = $l[1];
            } elseif (is_array($headers[$l[0]])) {
                $headers[$l[0]][] = $l[1];
            } else {
                $headers[$l[0]] = [$headers[$l[0]], $l[1]];
            }
        }

        return $headers;
    }

    /**
     * Parses a header line into an array containing the key and value in
     * positional elements.
     *
     * @param string $line Header line to parse
     *
     * @return array
     */
    public static function parseHeaderLine($line)
    {
        $headerParts = explode(':', $line, 2);

        return [
            $headerParts[0],
            isset($headerParts[1]) ? trim($headerParts[1]) : ''
        ];
    }
}
