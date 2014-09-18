===============
Client Adapters
===============

Client adapters accept a request array and return a response array or
``GuzzleHttp\Ring\RingFutureInterface`` object.

Built-In Adapters
-----------------

Guzzle-Ring comes with three built-in client adapters.

Stream Adapter
~~~~~~~~~~~~~~

The ``GuzzleHttp\Ring\Client\StreamAdapter`` uses PHP's
`http stream wrapper <http://php.net/manual/en/wrappers.http.php>`_ to send
requests. This adapter cannot send requests concurrently.

You can provide an associative array of custom stream context options to the
StreamAdapter using the ``stream_context`` key of the ``client`` request
option.

.. code-block:: php

    use GuzzleHttp\Ring\Client\StreamAdapter;

    $res = $adapter([
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

cURL Adapter
~~~~~~~~~~~~

The ``GuzzleHttp\Ring\Client\CurlAdapter`` can be used with PHP 5.5+ to send
requests using cURL easy handles. This adapter is great for sending requests
on at a time, but is unable to send requests concurrently.

When using the CurlAdapter, custom curl options can be specified as an
associative array of curl option constants mapping to values in the **curl**
key of the ``client`` key of the request.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlAdapter;

    $adapter = new CurlAdapter();

    $request = [
        'http_method' => 'GET',
        'headers'     => ['host' => [Server::$host]],
        'client'      => ['curl' => [CURLOPT_LOW_SPEED_LIMIT => 10]]
    ];

    $adapter($request);

cURL Multi Adapter
~~~~~~~~~~~~~~~~~~

The ``GuzzleHttp\Ring\Client\CurlMultiAdapter`` transfers requests using
cURL and returns future responses. The CurlMultiAdapter is great for sending
requests concurrently.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    $adapter = new CurlMultiAdapter();

    $request = [
        'http_method' => 'GET',
        'headers'     => ['host' => [Server::$host]]
    ];

    // this call returns immediately.
    $response = $adapter($request);

    // Block until the response completes.
    $response->deref();

Like the CurlAdapter, the CurlMultiAdapter accepts cusotm curl option in the
``curl`` key of the ``client`` request option.

Mock Adapter
~~~~~~~~~~~~

The ``GuzzleHttp\Ring\Client\MockAdapter`` is used to return mock responses.
When constructed, the adapter can be configured to return the same response
array over and over, a future response, or a the evaluation of a callback
function. This class is useful for implementing mock responses while still
accounting for things like the ``then`` request option.

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
``__invoke`` magic method). The callable accepts a request array and returns
a response array or future.

Adapters need to follow a few simple rules:

1. Do not throw exceptions. If an error is encountered, return an array that
   contains the ``error`` key that has an ``\Exception`` value.
2. If present, call the request's ``then`` option immediately when a response
   completes. When calling the ``then`` function pass the response by reference
   to allow the callback to modify the response as needed.
3. Return a response array or an instance of
   ``GuzzleHttp\Ring\RingFutureInterface``.
4. Complete any outstanding requests when the adapter is destructed, but do
   not dereference the futures.
