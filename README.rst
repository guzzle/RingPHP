===========
Guzzle-Ring
===========

A PHP port of Clojure's `Ring <https://github.com/ring-clojure/ring>`_, but
modified slightly to accomadate both clients and servers for both blocking and
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

Adapters are implemented as functions with two arguments: a handler and an
associative array of options. The options map provides any needed configuration
to the adapter, such as the port on which to run.

Once initialized, adapters receive HTTP requests, parse them to construct a
request array, and then invoke their handler with this request array as an
argument. Once the handler returns a response array, the adapter uses it to
construct and send an HTTP response to the client.

Request Array
-------------

A request array is a PHP associative array that contains the following keys
and corresponding values:

server_port
    (integer)
    The port on which the request is being handled. Required when using a
    Ring server.

server_name
    (string)
    The resolved server name, or the server IP address. Required when using
    a Ring server.

remote_addr
    (string)
    The IP address of the client or the last proxy that sent the request.
    Required when using a Ring server.

uri
    (string, required)
    The request URI excluding the query string. Must start with "/".

query_string
    (string)
    The query string, if present.

scheme
    (string)
    The transport protocol, must be one of ``http`` or ``https``. Defaults to
    ``http``.

request_method
    (string, required)
    The HTTP request method, must be all caps corresponding to a HTTP request
    method, such as GET or POST.

headers
    (required, array)
    Associative array of headers. Each key represents the header name. Values
    can be strings or arrays. When an array is provided, it means the header
    is sent using multiple lines.

body
    (string, fopen resource, ``Iterator``, ``GuzzleHttp\Stream\StreamInterface``)
    The body of the request, if present. Can be a string, resource returned
    from fopen, an ``Iterator`` that yields chunks of data, an object that
    implemented ``__toString``, or a ``GuzzleHttp\Stream\StreamInterface``.

Response Array
--------------

status
    (Required, integer)
    The HTTP status code. The status code MAY be set to ``null`` in the event
    an error occured before a response was received (e.g., a networking error).

headers
    (Required, array)
    Associative array of headers. Each key represents the header name. Values
    can be strings or arrays. When an array is provided, it means the header
    is sent using multiple lines. The headers array MAY be empty in the event
    an error occured before a response was received.

body
    (string, fopen resource, ``Iterator``, ``GuzzleHttp\Stream\StreamInterface``)
    The body of the response, if present. Can be a string, resource returned
    from fopen, an ``Iterator`` that yields chunks of data, an object that
    implemented ``__toString``, or a ``GuzzleHttp\Stream\StreamInterface``.

effective_url
    (string)
    The URL that returned the resulting response.

error
    (``\Ring\AdapterException``)
    Contains an exception describing any errors that were encountered during
    the transfer.

transfer_stats
    (array)
    Provides an associative array of transfer statistics.
