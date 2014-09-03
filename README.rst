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

client
    (array)
    Associative array of client specific transfer options.

Client Options
~~~~~~~~~~~~~~

The ``client`` request key value pair can contain the following keys:

cert
    (string, array)
    Set to a string to specify the path to a file containing a PEM formatted
    client side certificate. If a password is required, then set to an array
    containing the path to the PEM file in the first array element followed by
    the password required for the certificate in the second array element.

connect_timeout
    (float)
    Float describing the number of seconds to wait while trying to connect to a
    server. Use 0 to wait indefinitely (the default behavior).

debug
    (bool, fopen() resource)
    Set to true or set to a PHP stream returned by fopen() to enable debug
    output with the adapter used to send a request. For example, when using
    cURL to transfer requests, cURL's verbose of CURLOPT_VERBOSE will be
    emitted. When using the PHP stream wrapper, stream wrapper notifications
    will be emitted. If set to true, the output is written to PHP's STDOUT. If
    a PHP stream is provided, output is written to the stream.

decode_content
    (bool)
    Specify whether or not Content-Encoding responses (gzip, deflate, etc.) are
    automatically decoded.

proxy
    (string, array)
    Pass a string to specify an HTTP proxy, or an associative array to specify
    different proxies for different protocols where the scheme is the key and
    the value is the proxy address.

ssl_key
    (string, array)
    Specify the path to a file containing a private SSL key in PEM format. If a
    password is required, then set to an array containing the path to the SSL
    key in the first array element followed by the password required for the
    certificate in the second element.

save_to
    (string, fopen resource, ``GuzzleHttp\Stream\StreamInterface``)
    Specifies where the body of the response is downloaded. Pass a string to
    open a local file on disk and save the output to the file. Pass an fopen
    resource to save the output to a PHP stream resource. Pass a
    ``GuzzleHttp\Stream\StreamInterface`` to save the output to a Guzzle
    StreamInterface. Omitting this option will typically save the body of a
    response to a PHP temp stream.

stream
    (bool)
    Set to true to stream a response rather than download it all up-front. This
    option will only be utilized when the corresponding adapter supports it.

timeout
    (float)
    Float describing the timeout of the request in seconds. Use 0 to wait
    indefinitely (the default behavior).

verify
    (bool, string)
    Describes the SSL certificate verification behavior of a request. Set to
    true to enable SSL certificate verification using the system CA bundle
    when available (the default). Set to false to disable certificate
    verification (this is insecure!). Set to a string to provide the path to a
    CA bundle on disk to enable verification using a custom certificate.

version
    (string)
    HTTP protocol version to use with the request.

curl
    (array)
    Used by cURL adapters only. Specifies an array of CURLOPT_* options to
    use with a request.

stream_context
    (array)
    Used by PHP stream wrapper adapters only. Specifies an array of
    `stream context options <http://www.php.net/manual/en/context.php>`_.
    The stream_context array is an associative array where each key is a PHP
    transport, and each value is an associative array of transport options.

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
