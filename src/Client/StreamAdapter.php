<?php
namespace GuzzleHttp\Ring\Client;

use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\HandlerException;
use GuzzleHttp\Stream\InflateStream;
use GuzzleHttp\Stream\StreamInterface;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Stream\Utils;

/**
 * Guzzle-Ring client adapter that uses PHP's HTTP stream wrapper.
 */
class StreamAdapter
{
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function __invoke(array $request)
    {
        $url = Core::url($request);

        try {
            $stream = $this->createStream($url, $request, $headers);
            $parts = explode(' ', array_shift($headers), 3);
            $response = [
                'status'         => $parts[1],
                'reason'         => isset($parts[2]) ? $parts[2] : null,
                'headers'        => Core::headersFromLines($headers),
                'effective_url'  => $url
            ];
            $stream = $this->checkDecode($request, $response, $stream);
            $stream = $this->checkStreaming($request, $stream);
            $response['body'] = $stream;
            return $response;
        } catch (\Exception $e) {
            return $this->createErrorResponse($url, $request, $e);
        }
    }

    private function checkDecode(array $request, array $response, $stream)
    {
        // Automatically decode responses when instructed.
        if (!empty($request['client']['decode_content'])) {
            switch (Core::header($response, 'Content-Encoding', true)) {
                case 'gzip':
                case 'deflate':
                    $stream = new InflateStream(Stream::factory($stream));
                    break;
            }
        }

        return $stream;
    }

    private function checkStreaming(array $request, $stream)
    {
        // If not streaming, then drain the response into a stream.
        if (empty($request['client']['stream'])) {
            $dest = isset($request['client']['save_to'])
                ? $request['client']['save_to']
                : fopen('php://temp', 'r+');
            $stream = $this->drain($stream, $dest);
        }

        return $stream;
    }

    /**
     * Drains the stream into the "save_to" client option.
     *
     * @param resource                        $stream
     * @param string|resource|StreamInterface $dest
     *
     * @return Stream
     * @throws \RuntimeException when the save_to option is invalid.
     */
    private function drain($stream, $dest)
    {
        if (is_resource($stream)) {
            if (!is_resource($dest)) {
                $stream = Stream::factory($stream);
            } else {
                stream_copy_to_stream($stream, $dest);
                fclose($stream);
                rewind($dest);
                return $dest;
            }
        }

        // Stream the response into the destination stream
        $dest = is_string($dest)
            ? new Stream(Utils::open($dest, 'r+'))
            : Stream::factory($dest);

        Utils::copyToStream($stream, $dest);
        $dest->seek(0);
        $stream->close();

        return $dest;
    }

    /**
     * Creates an error response for the given stream.
     *
     * @param            $url
     * @param            $request
     * @param \Exception $e
     *
     * @return array
     */
    private function createErrorResponse($url, $request, \Exception $e)
    {
        if (!($e instanceof HandlerException)) {
            $e = new HandlerException($e->getMessage(), 0, $e);
        }

        return [
            'status'        => null,
            'body'          => null,
            'headers'       => [],
            'effective_url' => $url,
            'error'         => $e
        ];
    }

    /**
     * Create a resource and check to ensure it was created successfully
     *
     * @param callable $callback Callable that returns stream resource
     *
     * @return resource
     * @throws \RuntimeException on error
     */
    private function createResource(callable $callback)
    {
        // Turn off error reporting while we try to initiate the request
        $level = error_reporting(0);
        $resource = call_user_func($callback);
        error_reporting($level);

        // If the resource could not be created, then grab the last error and
        // throw an exception.
        if (!is_resource($resource)) {
            $message = 'Error creating resource: ';
            foreach ((array) error_get_last() as $key => $value) {
                $message .= "[{$key}] {$value} ";
            }
            throw new \RuntimeException(trim($message));
        }

        return $resource;
    }

    private function createStream(
        $url,
        array $request,
        &$http_response_header
    ) {
        static $methods;
        if (!$methods) {
            $methods = array_flip(get_class_methods(__CLASS__));
        }

        // HTTP/1.1 streams using the PHP stream wrapper require a
        // Connection: close header
        if ((!isset($request['version']) || $request['version'] == '1.1')
            && !Core::header($request, 'Connection')
        ) {
            $request['headers']['Connection'] = 'close';
        }

        $params = [];
        $options = $this->getDefaultOptions($request);

        if (isset($request['client'])) {
            foreach ($request['client'] as $key => $value) {
                $method = "add_{$key}";
                if (isset($methods[$method])) {
                    $this->{$method}($request, $options, $value, $params);
                }
            }
        }

        return $this->createStreamResource(
            $url,
            $request,
            $options,
            $this->createStreamContext($request, $options, $params),
            $http_response_header
        );
    }

    private function getDefaultOptions(array $request)
    {
        $headers = [];
        foreach ($request['headers'] as $name => $value) {
            foreach ((array) $value as $val) {
                $headers[] = "$name: $val";
            }
        }

        $context = [
            'http' => [
                'method'           => $request['http_method'],
                'header'           => $headers,
                'protocol_version' => '1.1',
                'ignore_errors'    => true,
                'follow_location'  => 0
            ]
        ];

        $body = Core::body($request);
        if (isset($body)) {
            $context['http']['content'] = $body;
            // Prevent the HTTP adapter from adding a Content-Type header.
            if (!Core::header($request, 'Content-Type')) {
                $context['http']['header'][] .= "Content-Type:";
            }
        }

        return $context;
    }

    private function add_proxy(array $request, &$options, $value, &$params)
    {
        if (!is_array($value)) {
            $options['http']['proxy'] = $value;
        } else {
            $scheme = isset($request['scheme']) ? $request['scheme'] : 'http';
            if (isset($value[$scheme])) {
                $options['http']['proxy'] = $value[$scheme];
            }
        }
    }

    private function add_timeout(array $request, &$options, $value, &$params)
    {
        $options['http']['timeout'] = $value;
    }

    private function add_verify(array $request, &$options, $value, &$params)
    {
        if ($value === true) {
            // PHP 5.6 or greater will find the system cert by default. When
            // < 5.6, use the Guzzle bundled cacert.
            if (PHP_VERSION_ID < 50600) {
                $options['ssl']['cafile'] = ClientUtils::getDefaultCaBundle();
            }
        } elseif (is_string($value)) {
            $options['ssl']['cafile'] = $value;
            if (!file_exists($value)) {
                throw new \RuntimeException("SSL CA bundle not found: $value");
            }
        } elseif ($value === false) {
            $options['ssl']['verify_peer'] = false;
            return;
        } else {
            throw new \InvalidArgumentException('Invalid verify request option');
        }

        $options['ssl']['verify_peer'] = true;
        $options['ssl']['allow_self_signed'] = true;
    }

    private function add_cert(array $request, &$options, $value, &$params)
    {
        if (is_array($value)) {
            $options['ssl']['passphrase'] = $value[1];
            $value = $value[0];
        }

        if (!file_exists($value)) {
            throw new \RuntimeException("SSL certificate not found: {$value}");
        }

        $options['ssl']['local_cert'] = $value;
    }

    private function add_debug(array $request, &$options, $value, &$params)
    {
        static $map = [
            STREAM_NOTIFY_CONNECT       => 'CONNECT',
            STREAM_NOTIFY_AUTH_REQUIRED => 'AUTH_REQUIRED',
            STREAM_NOTIFY_AUTH_RESULT   => 'AUTH_RESULT',
            STREAM_NOTIFY_MIME_TYPE_IS  => 'MIME_TYPE_IS',
            STREAM_NOTIFY_FILE_SIZE_IS  => 'FILE_SIZE_IS',
            STREAM_NOTIFY_REDIRECTED    => 'REDIRECTED',
            STREAM_NOTIFY_PROGRESS      => 'PROGRESS',
            STREAM_NOTIFY_FAILURE       => 'FAILURE',
            STREAM_NOTIFY_COMPLETED     => 'COMPLETED',
            STREAM_NOTIFY_RESOLVE       => 'RESOLVE'
        ];

        static $args = ['severity', 'message', 'message_code',
            'bytes_transferred', 'bytes_max'];

        if (!is_resource($value)) {
            $value = fopen('php://output', 'w');
        }

        $ident = $request['http_method'] . ' ' . Core::url($request);
        $params['notification'] = function () use ($ident, $value, $map, $args) {
            $passed = func_get_args();
            $code = array_shift($passed);
            fprintf($value, '<%s> [%s] ', $ident, $map[$code]);
            foreach (array_filter($passed) as $i => $v) {
                fwrite($value, $args[$i] . ': "' . $v . '" ');
            }
            fwrite($value, "\n");
        };
    }

    private function applyCustomOptions(array $request, array &$options)
    {
        // Overwrite any generated options with custom options
        if (isset($request['client']['stream_context'])) {
            if (!is_array($request['client']['stream_context'])) {
                throw new \RuntimeException('stream_context must be an array');
            }
            $options = array_replace_recursive(
                $options,
                $request['client']['stream_context']
            );
        }
    }

    private function createStreamContext(
        array $request,
        array $options,
        array $params
    ) {
        $this->applyCustomOptions($request, $options);
        return $this->createResource(
            function () use ($request, $options, $params) {
                return stream_context_create($options, $params);
            },
            $request,
            $options
        );
    }

    private function createStreamResource(
        $url,
        array $request,
        array $options,
        $context,
        &$http_response_header
    ) {
        return $this->createResource(
            function () use ($url, &$http_response_header, $context) {
                if (false === strpos($url, 'http')) {
                    trigger_error("URL is invalid: {$url}", E_USER_WARNING);
                    return null;
                }
                return fopen($url, 'r', null, $context);
            },
            $request,
            $options
        );
    }
}
