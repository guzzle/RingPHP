===============
Client Adapters
===============

Client adapters accept a request array and return a future response array that
can be used synchronously as an array or asynchronously using a promise.

Built-In Adapters
-----------------

Guzzle-Ring comes with three built-in client adapters.

Stream Adapter
~~~~~~~~~~~~~~

The ``GuzzleHttp\Ring\Client\StreamAdapter`` uses PHP's
`http stream wrapper <http://php.net/manual/en/wrappers.http.php>`_ to send
requests.

.. note::

    This adapter cannot send requests concurrently.

You can provide an associative array of custom stream context options to the
StreamAdapter using the ``stream_context`` key of the ``client`` request
option.

.. code-block:: php

    use GuzzleHttp\Ring\Client\StreamAdapter;

    $response = $adapter([
        'http_method' => 'GET',
        'uri' => '/',
        'headers' => ['host' => ['httpbin.org']],
        'client' => [
            'stream_context' => [
                'http' => [
                    'request_fulluri' => true,
                    'method' => 'HEAD'
                ],
                'socket' => [
                    'bindto' => '127.0.0.1:0'
                ],
                'ssl' => [
                    'verify_peer' => false
                ]
            ]
        ]
    ]);

    // The response has already completed
    assert(true == $response->realized());

    // Even though it's already completed, you can still use a promise
    $response->then(function ($response) {
        echo $response['status']; // 200
    });

    // Or access the response using the future interface
    echo $response['status']; // 200

cURL Adapter
~~~~~~~~~~~~

The ``GuzzleHttp\Ring\Client\CurlAdapter`` can be used with PHP 5.5+ to send
requests using cURL easy handles. This adapter is great for sending requests
one at a time because the execute and select loop is implemented in C code
which executes faster and consumes less memory.

.. note::

    This adapter cannot send requests concurrently.

When using the CurlAdapter, custom curl options can be specified as an
associative array of `cURL option constants <http://php.net/manual/en/curl.constants.php>`_
mapping to values in the **curl** key of the ``client`` key of the request.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlAdapter;

    $adapter = new CurlAdapter();

    $request = [
        'http_method' => 'GET',
        'headers'     => ['host' => [Server::$host]],
        'client'      => ['curl' => [CURLOPT_LOW_SPEED_LIMIT => 10]]
    ];

    $response = $adapter($request);

    // The response will always have completed when it is returned.
    assert(true == $response->realized());

    // Can be used as a future
    echo $response['status']; // 200

    // Can be used as a promise
    $response->then(function ($response) {
        echo $response['status']; // 200
    });

cURL Multi Adapter
~~~~~~~~~~~~~~~~~~

The ``GuzzleHttp\Ring\Client\CurlMultiAdapter`` transfers requests using
cURL's `multi API <http://curl.haxx.se/libcurl/c/libcurl-multi.html>`_. The
``CurlMultiAdapter`` is great for sending requests concurrently.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    $adapter = new CurlMultiAdapter();

    $request = [
        'http_method' => 'GET',
        'headers'     => ['host' => [Server::$host]]
    ];

    // this call returns immediately.
    $response = $adapter($request);

    // Ideally, you should use the promise API to not block.
    $response->then(function ($response) {
        // Got the response at some point in the future
        echo $response['status']; // 200
        // Don't break the chain
        return $response;
    })->then(function ($response) {
        // ...
    });

    // If you really need to block, then you can use the response as an
    // associative array. This will block until it has completed.
    echo $response['status']; // 200

Just like the ``CurlAdapter``, the ``CurlMultiAdapter`` accepts cusotm curl
option in the ``curl`` key of the ``client`` request option.

Mock Adapter
~~~~~~~~~~~~

The ``GuzzleHttp\Ring\Client\MockAdapter`` is used to return mock responses.
When constructed, the adapter can be configured to return the same response
array over and over, a future response, or a the evaluation of a callback
function.

.. code-block:: php

    use GuzzleHttp\Ring\Client\MockAdapter;

    // Return a canned repsonse.
    $mock = new MockAdapter(['status' => 200]);
    $response = $mock([]);
    assert(200 == $response['status']);
    assert([] == $response['headers']);

Implementing Adapters
---------------------

Client adapters are just PHP callables (functions or classes that have the
``__invoke`` magic method). The callable accepts a request array and MUT return
an instance of ``GuzzleHttp\Ring\Future\FutureArrayInterface`` so that the
response can be used by both blocking and non-blocking consumers.

Adapters need to follow a few simple rules:

1. Do not throw exceptions. If an error is encountered, return an array that
   contains the ``error`` key that maps to an ``\Exception`` value.
2. If the request has a ``delay`` client option, then the adapter should only
   send the request after the specified delay time in seconds. Blocking
   adapters may find it convenient to just let the
   ``GuzzleHttp\Ring\Core::doSleep($request)`` function handle this for them.
3. Always return an instance of ``GuzzleHttp\Ring\Future\FutureArrayInterface``.
4. Complete any outstanding requests when the adapter is destructed.
