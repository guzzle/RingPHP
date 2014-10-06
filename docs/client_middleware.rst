=================
Client Middleware
=================

Middleware intercepts requests before they are sent over the wire and can be
used to add functionality to adapters.

Modifying Requests
------------------

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

    // Create a new handler that adds headers to each request.
    $adapter = $addHeaderHandler($adapter, [
        'X-AddMe'       => 'hello',
        'Authorization' => 'Basic xyz'
    ]);

    $response = $adapter([
        'http_method' => 'GET',
        'headers'     => ['Host' => ['httpbin.org']
    ]);

Modifying Responses
-------------------

You can change a response as it's returned from a middleware. Remember that
responses returned from an adapter (including middleware) must implement
``GuzzleHttp\Ring\Future\FutureArrayInterface``. In order to be a good citizen,
you should not expect that the responses returned through your middleware will
be completed synchronously. Instead, you should use the
``GuzzleHttp\Ring\Core::proxy()`` function to modify the response when the
underlying promise is resolved. This function is a helper function that makes it
easy to create a new instance of ``FutureArrayInterface`` that wraps an existing
``FutureArrayInterface`` object.

Let's say you wanted to add headers to a response as they are returned from
your middleware, but you want to make sure you aren't causing future
responses to be dereferenced right away. You can achieve this by modifying the
incoming request and using the ``Core::proxt`` function.

.. code-block:: php

    use GuzzleHttp\Ring\Core;
    use GuzzleHttp\Ring\Client\CurlAdapter;

    $adapter = new CurlAdapter();

    $responseHeaderHandler = function (callable $handler, array $headers) {
        return function (array $request) use ($handler, $headers) {
            // Send the request using the wrapped and return the response.
            return Core::proxy($handler($request), function ($response) use ($headers) {
                // Add the headers to the response when it is available.
                foreach ($headers as $key => $value) {
                    $response['headers'][$key] = (array) $value;
                }
                return $response;
            });
        }
    };

    // Create a new handler that adds headers to each response.
    $adapter = $responseHeaderHandler($adapter, ['X-Header' => 'hello!']);

    $response = $adapter([
        'http_method' => 'GET',
        'headers'     => ['Host' => ['httpbin.org']
    ]);

    assert($response['headers']['X-Header'] == 'hello!');

Built-In Middleware
-------------------

Guzzle-Ring comes with a few basic client middlewares that modify requests
and responses.

Streaming Middleware
~~~~~~~~~~~~~~~~~~~~

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
~~~~~~~~~~~~~~~~~

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
