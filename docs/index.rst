===========
Guzzle-Ring
===========

Provides lower-level APIs used to power HTTP clients and servers through a
simple, PHP ``callable`` that accepts a request hash and returns a response
hash. Guzzle-Ring is inspired by Clojure's `Ring <https://github.com/ring-clojure/ring>`_,
but modified to accomadate both clients and servers for both blocking and
non-blocking requests.

Guzzle-Ring is utilized in `Guzzle <http://guzzlephp.org>`_ 5.0+ to send HTTP
requests.

.. toctree::
   :maxdepth: 1

   spec
   futures
   client_middleware
   client_adapters
   testing
