===========
Guzzle-Ring
===========

Provides lower-level APIs used to power HTTP clients and servers through a
simple, PHP ``callable`` that accepts a request hash and returns a response
hash. Guzzle-Ring is inspired by Clojure's `Ring <https://github.com/ring-clojure/ring>`_,
but modified to accomadate both clients and servers for both blocking and
non-blocking requests.

Specification
=============

Handlers
--------

Ring handlers constitute the core logic of a web application. Handlers are
implemented as PHP callables that process a given request associative array to
generate and return a response associative array (or an array-like object that
implements ``ArrayAccess``, ``Iterator``, and ``Countable``).

Middleware
----------

Ring middleware augments the functionality of handlers by invoking them in the
process of generating responses. Middleware is typically implemented as a
higher-order function or callable that typically take one ore more handlers as
arguments followed by an optional associative array of options as the last
argument, returning a new handler with the desired compound behavior.

Adapters
--------

Handlers are run via Ring adapters, which are in turn responsible for
implementing the HTTP protocol and abstracting the handlers that they run from
the details of the protocol.

Client Adapters
~~~~~~~~~~~~~~~

Client adapters are implemented exactly like Handlers, except that they
actually create and return HTTP responses after sending a request over the
wire.

Server Adapters
~~~~~~~~~~~~~~~

Server adapters are implemented as functions with two arguments: a handler and
an associative array of options. The options map provides any needed
configuration to the adapter, such as the port on which to run.

Once initialized, adapters receive HTTP requests, parse them to construct a
request array, and then invoke their handler with this request array as an
argument. Once the handler returns a response array, the adapter uses it to
construct and send an HTTP response to the client.

Request Array
-------------

A request array is a PHP associative array that contains the following keys
and corresponding values:

request_method
    (string, required) The HTTP request method, must be all caps corresponding
    to a HTTP request method, such as GET or POST.

scheme
    (string) The transport protocol, must be one of ``http`` or ``https``.
    Defaults to ``http``.

uri
    (string, required) The request URI excluding the query string. Must
    start with "/".

query_string
    (string) The query string, if present.

headers
    (required, array) Associative array of headers. Each key represents the
    header name. Each value contains an array of strings where each entry of
    the array SHOULD be sent over the wire on a separate header line.

body
    (string, fopen resource, ``Iterator``, ``GuzzleHttp\Stream\StreamInterface``)
    The body of the request, if present. Can be a string, resource returned
    from fopen, an ``Iterator`` that yields chunks of data, an object that
    implemented ``__toString``, or a ``GuzzleHttp\Stream\StreamInterface``.

server_port
    (integer) The port on which the request is being handled. Required when
    using a Ring server.

server_name
    (string) The resolved server name, or the server IP address. Required when
    using a Ring server.

remote_addr
    (string) The IP address of the client or the last proxy that sent the
    request. Required when using a Ring server.

client
    (array) Associative array of client specific transfer options (described
    later in the document).

then
    (callable) A function that is invoked immediately after a request/response
    transaction has completed. The callable is provided the response array and
    MAY return a new response value that will be used instead of the provided
    response array. If no value is returned by the callable, then the passed
    in response array argument will be the response returned by the adapter.
    The option is particularly useful for non-blocking adapters, but MUST be
    emulated by blocking adapters as well to provide a consistent
    implementation.

future
    (bool) Specifies whether or not a request SHOULD be a non-blocking Future.
    By default, responses can be either actual response arrays or
    ``GuzzleHttp\Ring\Future`` objects which act like associative arrays but
    are fulfilled asynchronously or when they are accessed.

    Future responses created by asynchronous adapters MUST attempt to complete
    any outstanding future responses when a process completes. Asynchronous
    adapter MAY choose to automatically complete responses when the number
    of outstanding requests reaches an adapter-specific threshold.

Response Array
--------------

status
    (Required, integer) The HTTP status code. The status code MAY be set to
    ``null`` in the event an error occurred before a response was received
    (e.g., a networking error).

headers
    (Required, array) Associative array of headers. Each key represents the
    header name. Each value contains an array of strings where each entry of
    the array is a header line. The headers array MAY be empty in the event an
    error occurred before a response was received.

body
    (string, fopen resource, ``Iterator``, ``GuzzleHttp\Stream\StreamInterface``)
    The body of the response, if present. Can be a string, resource returned
    from fopen, an ``Iterator`` that yields chunks of data, an object that
    implemented ``__toString``, or a ``GuzzleHttp\Stream\StreamInterface``.

effective_url
    (string) The URL that returned the resulting response.

error
    (``GuzzleHttp\Ring\HandlerAdapter``) Contains an exception describing any
    errors that were encountered during the transfer.

transfer_stats
    (array) Provides an associative array of arbitrary transfer statistics if
    provided by the underlying adapter.

Client Specific Options
-----------------------

The ``client`` request key value pair can contain the following keys:

cert
    (string, array) Set to a string to specify the path to a file containing a
    PEM formatted client side certificate. If a password is required, then set
    to an array containing the path to the PEM file in the first array element
    followed by the password required for the certificate in the second array
    element.

connect_timeout
    (float) Float describing the number of seconds to wait while trying to\
    connect to a server. Use 0 to wait indefinitely (the default behavior).

debug
    (bool, fopen() resource) Set to true or set to a PHP stream returned by
    fopen() to enable debug output with the adapter used to send a request. For
    example, when using cURL to transfer requests, cURL's verbose of
    CURLOPT_VERBOSE will be emitted. When using the PHP stream wrapper,
    stream wrapper notifications will be emitted. If set to true, the output
    is written to PHP's STDOUT. If a PHP stream is provided, output is written
    to the provided stream.

decode_content
    (bool) Specify whether or not Content-Encoding responses (gzip, deflate,
    etc.) are automatically decoded.

progress
    (function) Defines a function to invoke when transfer progress is made.
    The function accepts the following arguments: the total number of bytes
    expected to be downloaded, the number of bytes downloaded so far, the
    number of bytes expected to be uploaded, and the number of bytes uploaded
    so far.

proxy
    (string, array) Pass a string to specify an HTTP proxy, or an associative
    array to specify different proxies for different protocols where the scheme
    is the key and the value is the proxy address.

ssl_key
    (string, array) Specify the path to a file containing a private SSL key in
    PEM format. If a password is required, then set to an array containing the
    path to the SSL key in the first array element followed by the password
    required for the certificate in the second element.

save_to
    (string, fopen resource, ``GuzzleHttp\Stream\StreamInterface``)
    Specifies where the body of the response is downloaded. Pass a string to
    open a local file on disk and save the output to the file. Pass an fopen
    resource to save the output to a PHP stream resource. Pass a
    ``GuzzleHttp\Stream\StreamInterface`` to save the output to a Guzzle
    StreamInterface. Omitting this option will typically save the body of a
    response to a PHP temp stream.

stream
    (bool) Set to true to stream a response rather than download it all
    up-front. This option will only be utilized when the corresponding adapter
    supports it.

timeout
    (float) Float describing the timeout of the request in seconds. Use 0 to
    wait indefinitely (the default behavior).

verify
    (bool, string) Describes the SSL certificate verification behavior of a
    request. Set to true to enable SSL certificate verification using the
    system CA bundle when available (the default). Set to false to disable
    certificate verification (this is insecure!). Set to a string to provide
    the path to a CA bundle on disk to enable verification using a custom
    certificate.

version
    (string) HTTP protocol version to use with the request.

cURL Specific Options
~~~~~~~~~~~~~~~~~~~~~

The following options are provided in a request's ``client`` key value pair.
These options are used by all cURL powered adapters.

curl
    (array) Used by cURL adapters only. Specifies an array of CURLOPT_* options
    to use with a request.

PHP Stream wrapper specific options
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The following options are provided in a request's ``client`` key value pair.
These options are used by all PHP stream wrapper powered adapters.

stream_context
    (array) Used by PHP stream wrapper adapters only. Specifies an array of
    `stream context options <http://www.php.net/manual/en/context.php>`_.
    The stream_context array is an associative array where each key is a PHP
    transport, and each value is an associative array of transport options.

Client Usage
------------

Because client adapters are just callables, they are used like PHP functions.
They accept a request hash and return a response hash.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlAdapter;

    $adapter = new CurlAdapter();

    // requests are arrays
    $request = [
        'http_method'  => 'GET',
        'uri'          => '/',
        'query_string' => 'foo=bar',
        'headers'      => [
            'Host'  => ['google.com'],     // headers are arrays
            'X-Foo' => ['Bar, Baz', 'Bam']
        ]
    ];

    $response = $adapter($request);

    // Exceptions are added to the error key
    if (isset($response['error'])) {
        throw $response['error'];
    }

    // Responses are arrays
    echo $response['status']; // 200
    echo $response['headers']['Set-Cookie'][0]; // Cookie stuff

If an error is encountered while sending a request, the ``error`` key will be
populated with a ``GuzzleHttp\Ring\HandlerException`` exception. Well behaved
adapters do not ever throw exceptions unless absolutely necessary. Instead,
they should add an exception to the ``error`` key.

Notice that all ``headers`` values are arrays. Each entry in the array is a
string that should be sent over the wire on its own line (if the underlying
adapter allows).

Future Responses
~~~~~~~~~~~~~~~~

Clients may return future responses if they wish. Future responses are just
like response arrays except that they are actually ``GuzzleHttp\Ring\Future``
objects that are not sent over the wire until they are used or the underlying
adapter needs to send outstanding requests (for example, if the number of
queued requests becomes too high or the adapter is shutting down).

.. code-block:: php

    use GuzzleHttp\Ring\Future;
    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    $adapter = new CurlMultiAdapter();

    $request = [
        'http_method'  => 'GET',
        'uri'          => '/',
        'headers'      => ['Host' => ['google.com']]
    ];

    $responses = [];
    for ($i = 0; $i < 10; $i++) {
        $responses[] = $adapter($request);
    }

    // They're all Future objects that have not yet been sent.
    assert($responses[0] instanceof Future);

    // We can prevent a future from being sent by cancelling it.
    $responses[1]->cancel();

    // Accessing a future will cause it to block until it's complete.
    echo $responses[0]['status']; // 200

Note: Futures that are not completed by the time the underlying adapter is
destructed will be completed when the adapter is shutting down.

Causing a future to "dereference" or block until it completes will also cause
the other futures that have been queued on an adapter to block until they
complete. If you need something to happen the instant a future completes, then
you must use the ``then`` array key of a request. The ``then`` key must be
given a PHP callable that accepts a response array. If the callable returns
a response array, then the returned response will be uses as the new response
of the request.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    // The CurlMultiAdapter creates future responses by default.
    $adapter = new CurlMultiAdapter();

    // This function is called when each request completes.
    $afterComplete = function (array $response) {
        if (isset($response['error'])) {
            echo "Error: " . $response['error']->getMessage() . "\n";
        } else {
            echo "Completed request to: {$response['effective_url']}\n";
        }
    };

    $request = [
        'http_method'  => 'GET',
        'uri'          => '/',
        'headers'      => ['Host' => ['www.google.com']],
        'then'         => $afterComplete
    ];

    // Queue up a bunch of futures the be sent in parallel.
    for ($i = 0; $i < 5; $i++) {
        $adapter($request);
    }

    // Send a failing request
    $request['headers']['Host'] = ['doesnotexist.co.uk'];
    $adapter($request);

Using Middleware
~~~~~~~~~~~~~~~~

Middleware intercepts requests before they are sent over the wire and can be
used to add functionality to adapters.

Let's say you wanted to modify requests before they are sent over the wire
so that they always add specific headers. This can be accomplished by creating
a function that accepts a handler and returns a new function that adds the
composed behavior.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlAdapter;

    $adapter = new CurlAdapter();

    $addHeaderHandler = function (callable $handler, array $headers = []) {
        return function (array $request) use ($handler, $headers) {
            // Add our custom headers
            foreach ($headers as $key => $value) {
                $request['headers'][$key] = $value;
            }

            // Send the request using the handler and return the response.
            return $handler($request);
        }
    };
    
    $adapter = $addHeaderHandler($adapter);

    $response = $adapter([
        'http_method' => 'GET',
        'headers'     => ['Host' => ['httpbin.org']
    ]);

This repository comes with a few basic client middlewares that modify requests
and responses.

Synchronous Middleware
^^^^^^^^^^^^^^^^^^^^^^

You can force all responses to be synchronous using the synchronous middleware:

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;
    use GuzzleHttp\Ring\Client\Middleware;

    $adapter = new CurlMultiAdapter();
    $synchronousHandler = Middleware::wrapSynchronous($adapter);

    // Send a request using an adapter that creates Future responses, but
    // the middleware will convert the future to a synchronous response before
    // returning.
    $response = $synchronousHandler([
        'http_method' => 'GET',
        'headers'     => ['Host' => ['www.google.com']
    ]);

    // The response has been dereferenced and is a regular array.
    assert(is_array($response));

Streaming Middleware
^^^^^^^^^^^^^^^^^^^^

If you want to send all requests with the ``streaming`` option to a specific
adapter but other requests to a different adapter, then use the streaming
middleware.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlAdapter;
    use GuzzleHttp\Ring\Client\StreamAdapter;
    use GuzzleHttp\Ring\Client\Middleware;

    $defaultAdapter = new CurlAdapter();
    $streamingAdapter = new StreamAdapter();
    $streamingHandler = Middleware::wrapStreaming(
        $defaultAdapter,
        $streamingAdapter
    );

    // Send the request using the streaming adapter.
    $response = $streamingHandler([
        'http_method' => 'GET',
        'headers'     => ['Host' => ['www.google.com'],
        'stream'      => true
    ]);

    // Send the request using the default adapter.
    $response = $streamingHandler([
        'http_method' => 'GET',
        'headers'     => ['Host' => ['www.google.com']
    ]);

Future Middleware
^^^^^^^^^^^^^^^^^

If you want to send all requests with the ``future`` option to a specific
adapter but other requests to a different adapter, then use the future
middleware. Like the synchronous middleware, this middleware converts future
responses to synchronous responses if the ``future`` request option was not set
to ``true`` on the request hash.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlAdapter;
    use GuzzleHttp\Ring\Client\CurlMultiAdapter;
    use GuzzleHttp\Ring\Client\Middleware;

    $defaultAdapter = new CurlAdapter();
    $futureAdapter = new CurlMultiAdapter();
    $futureHandler = Middleware::wrapFuture(
        $defaultAdapter,
        $futureAdapter
    );

    // Send the request using the blocking adapter.
    $response = $futureHandler([
        'http_method' => 'GET',
        'headers'     => ['Host' => ['www.google.com']
    ]);

    // Send the request using the future, non-blocking, adapter.
    $response = $futureHandler([
        'http_method' => 'GET',
        'headers'     => ['Host' => ['www.google.com'],
        'future'      => true
    ]);
