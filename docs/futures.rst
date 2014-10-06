=======
Futures
=======

Guzzle-Ring uses hybrid of futures and promises to provide a consistent API
that can be used for both blocking and non-blocking consumers. Futures
represent a computation that may have not yet completed. When a future is used,
or *dereferenced*, the future will either return the completed result
or block until the result is ready. Subsequent calls to dereference a future
will returned the previously completed result. Futures can be cancelled,
which stop the computation if possible.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    $response = $adapter([
        'http_method' => 'GET',
        'uri'         => '/',
        'headers'     => ['host' => ['httpbin.org']]
    ]);

    // You can block until a result is ready by using a future like a normal value
    echo $response['status'];

You can get the result of a future when it is ready using the promise interface
of a future. Futures expose a promise API via a ``then()`` method that utilizes
`ReactPHP's promise library <https://github.com/reactphp/promise>`_. You should
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

Future Responses
----------------

Guzzle-Ring uses futures to return asynchronous responses immediately. When a
future response is used, or *dereferenced*, the future will either return the
completed response or block until the response has completed and then return
it.

Client adapters always return future responses that implement
``GuzzleHttp\Ring\Future\ArrayFutureInterface``. These future responses act
just like normal PHP associative arrays and provide a promise interface.

.. important::

    Futures that are not completed by the time the underlying adapter is
    destructed will be completed when the adapter is shutting down.

Dereferencing
-------------

Dereferencing a future response will block until it the response complete.
While waiting on the response, other futures created by the same underlying
adapter will continue to be sent concurrently. If you need something to happen
the instant a future completes and do not wish to block, then you must use the
promise API of a future using the future's ``then()`` method.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    $adapter = new CurlMultiAdapter();

    $request = [
        'http_method'  => 'GET',
        'uri'          => '/',
        'headers'      => ['Host' => ['www.google.com']]
    ];

    $response = $adapter($request);

    // This will block!
    $response->deref();
    // This will implicitly call deref(), and will block too!
    $response['status'];

Cancelling
----------

Futures can be cancelled if they have not already been dereferenced. Cancelling
a future will prevent the future from executing the dereference function and,
if possible, will stop the request from sending.

Guzzle-Ring futures are typically implementing with the
``GuzzleHttp\Ring\Future\BaseFutureTrait``. This trait provides the cancellation
functionality that should be common to most implementations.

When a future is cancelled, the cancellation function is invoked and performs
the actual work needed to cancel the request from sending if possible
(e.g., telling an event loop to stop sending a request or to close a socket).
If no cancellation function is provided, then a request cannot be cancelled. If
a cancel function is provided, then it should accept the future as an argument
and return true if the future was successfully cancelled or false if it could
not be cancelled.
