===========
Guzzle-Ring
===========

Provides low level APIs used to power HTTP clients and servers through a
simple, PHP ``callable`` that accepts a request hash and returns a future
response hash. Guzzle-Ring supports both synchronous and asynchronous
workflows by utilizing both futures and `promises <https://github.com/reactphp/promise>`_.

Guzzle-Ring is inspired by Clojure's `Ring <https://github.com/ring-clojure/ring>`_,
but has been modified to accommodate clients and servers for both blocking
and non-blocking requests.

Guzzle-Ring is utilized as the adapter layer in
`Guzzle <http://guzzlephp.org>`_ 5.0+ to send HTTP requests.

.. toctree::
   :maxdepth: 1

   spec
   futures
   client_middleware
   client_adapters
   testing
