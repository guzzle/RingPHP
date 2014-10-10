=======
Futures
=======

Futures represent a computation that may have not yet completed. Guzzle-Ring
uses hybrid of futures and promises to provide a consistent API that can be
used for both blocking and non-blocking consumers.

Promises
--------

You can get the result of a future when it is ready using the promise interface
of a future. Futures expose a promise API via a ``then()`` method that utilizes
`React's promise library <https://github.com/reactphp/promise>`_. You should
use this API when you do not wish to block.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    $response = $adapter([
        'http_method' => 'GET',
        'uri'         => '/',
        'headers'     => ['host' => ['httpbin.org']]
    ]);

    // Use the then() method to use the promise API of the future.
    $response->then(function ($response) {
        echo $response['status'];
    });

You can get the promise used by the future, an instance of
``React\Promise\PromiseInterface``, by calling the ``promise()`` method of the
future.

.. code-block:: php

    $promise = $response->promise();

    $promise->then(function ($response) {
        echo $response['status'];
    });

This promise value can used with React's
`aggregate promise functions <https://github.com/reactphp/promise#functions>`_.

Waiting
-------

You can wait on a future to complete and retrieve the value, or *dereference*
the future, using the ``wait()`` method. Calling the ``wait()`` method of a
future will block until the result is available and return it. Subsequent calls
to dereference a future will return the previously completed result. Futures
can be cancelled, which stops the computation if possible.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    $response = $adapter([
        'http_method' => 'GET',
        'uri'         => '/',
        'headers'     => ['host' => ['httpbin.org']]
    ]);

    // You can explicitly call block to wait on a result.
    $realizedResponse = $response->wait();

    // Future responses can be used like a regular PHP array.
    echo $response['status'];

In addition to explicitly calling the ``wait()`` function, using a future like
a normal value will implicitly trigger the ``wait()`` function.

Future Responses
----------------

Guzzle-Ring uses futures to return asynchronous responses immediately. Client
adapters always return future responses that implement
``GuzzleHttp\Ring\Future\ArrayFutureInterface``. These future responses act
just like normal PHP associative arrays for blocking access and provide a
promise interface for non-blocking access.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    $adapter = new CurlMultiAdapter();

    $request = [
        'http_method'  => 'GET',
        'uri'          => '/',
        'headers'      => ['Host' => ['www.google.com']]
    ];

    $response = $adapter($request);

    // Use the promise API for non-blocking access to the response. The actual
    // response value will be delivered to the promise.
    $response->then(function ($response) {
        echo $response['status'];
    });

    // You can wait (block) until the future is completed.
    $response->wait();

    // This will implicitly call wait(), and will block too!
    $response['status'];

.. important::

    Futures that are not completed by the time the underlying adapter is
    destructed will be completed when the adapter is shutting down.

Cancelling
----------

Futures can be cancelled if they have not already been dereferenced. Cancelling
a future will prevent the future from executing the dereference function.

Guzzle-Ring futures are typically implemented with the
``GuzzleHttp\Ring\Future\BaseFutureTrait``. This trait provides the cancellation
functionality that should be common to most implementations. Cancelling a
future response will try to prevent the request from sending over the wire.

When a future is cancelled, the cancellation function is invoked and performs
the actual work needed to cancel the request from sending if possible
(e.g., telling an event loop to stop sending a request or to close a socket).
If no cancellation function is provided, then a request cannot be cancelled. If
a cancel function is provided, then it should accept the future as an argument
and return true if the future was successfully cancelled or false if it could
not be cancelled.
