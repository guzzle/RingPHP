# CHANGELOG

## 1.0.1 - 2014-10-26

- Fixed a header parsing issue with the `CurlHandler` and `CurlMultiHandler`
  that caused cURL requests with multiple responses to merge repsonses together
  (e.g., requests with digest authentication).

## 1.0.0 - 2014-10-12

Initial release.
