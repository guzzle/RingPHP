=======
Futures
=======

Futures represent a computation that may have not yet completed. When a future
is used, or *dereferenced*, the future will either return the completed result
or block until the result is ready. Subsequent calls to dereference a future
will returned the previously completed result. Futures can be cancelled,
which stop the computation if possible.

Future Responses
----------------

Guzzle-Ring uses futures to return asynchronous responses immediately. When a
future response is used, or *dereferenced*, the future will either return the
completed response or block until the response has completed and then return
it.

Client adapters may return future responses if they wish. Future responses are
just like response arrays except that they are actually instances of
``GuzzleHttp\Ring\RingFutureInterface`` that are not sent over the wire until
they are used or the underlying adapter needs to send outstanding requests
(for example, if the number of queued requests becomes too high or the adapter
is shutting down).

One client adapter that creates future responses by default is the
``GuzzleHttp\Ring\Client\CurlMultiAdapter``.

.. code-block:: php

    use GuzzleHttp\Ring\Future;
    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    $adapter = new CurlMultiAdapter();

    // Create the request that will be sent to the adapter.
    $request = [
        'http_method'  => 'GET',
        'uri'          => '/',
        'headers'      => ['Host' => ['google.com']]
    ];

    // Send a large number of requests and collect the future responses.
    $responses = [];
    for ($i = 0; $i < 10; $i++) {
        $responses[] = $adapter($request);
    }

    // They're all Future objects that have not yet been sent.
    assert($responses[0] instanceof Future);

    // Accessing a future will cause it to block until it's complete.
    echo $responses[0]['status']; // 200

.. important::

    Futures that are not completed by the time the underlying adapter is
    destructed will be completed when the adapter is shutting down. The adapter
    will not, however, dereference the future.

Dereferencing
-------------

Dereferencing a future response will block until it the response complete.
While waiting on the response, other futures created by the same underlying
adapter will continue to be sent concurrently. If you need something to happen
the instant a future completes, then you must use the ``then`` array key of a
request. The ``then`` key must be given a PHP callable that accepts a response
array by reference. You may modify the response array provided to the ``then``
callback to modify the response that is ultimately returned when the future is
dereferenced.

.. code-block:: php

    use GuzzleHttp\Ring\Client\CurlMultiAdapter;

    // The CurlMultiAdapter creates future responses by default.
    $adapter = new CurlMultiAdapter();

    // This function is called immediately when each request completes.
    $afterComplete = function (array &$response) {
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

Cancelling
----------

Futures can be cancelled if they have not already been dereferenced. Cancelling
a future will prevent the future from executing the dereference function and,
if possible, will stop the request from sending.

Guzzle-Ring futures are typically implementing with the
``GuzzleHttp\Ring\BaseFutureTrait``. This trait provides the cancellation
functionality that should be common to most implementations. The constructor
accepts a dereference function followed by an optional cancellation function.

When a future is cancelled, the cancellation function is invoked and performs
the actual work needed to cancel the request from sending if possible
(e.g., telling an event loop to stop sending a request or to close a socket).
If no cancellation function is provided, then a request cannot be cancelled. If
a cancel function is provided, then it should accept the future as an argument
and return true if the future was successfully cancelled or false if it could
not be cancelled.
