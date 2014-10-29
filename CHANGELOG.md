# CHANGELOG

## 1.0.2 - 2014-10-28

* Now correctly honoring a `version` option is supplied in a request.
  See https://github.com/guzzle/RingPHP/pull/8

## 1.0.1 - 2014-10-26

* Fixed a header parsing issue with the `CurlHandler` and `CurlMultiHandler`
  that caused cURL requests with multiple responses to merge repsonses together
  (e.g., requests with digest authentication).

## 1.0.0 - 2014-10-12

* Initial release.
