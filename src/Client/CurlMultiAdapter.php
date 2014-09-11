<?php
namespace GuzzleHttp\Ring\Client;

use GuzzleHttp\Ring\Future;

/**
 * Returns an asynchronous response using curl_multi_* functions.
 *
 * When using the CurlMultiAdapter, custom curl options can be specified as an
 * associative array of curl option constants mapping to values in the
 * **curl** key of the "client" key of the request.
 */
class CurlMultiAdapter
{
    /** @var callable */
    private $factory;

    /** @var resource */
    private $mh;

    /** @var int */
    private $selectTimeout;

    /** @var bool */
    private $active;

    private $handles = [];
    private $processed = [];
    private $futures = [];
    private $maxHandles;

    /**
     * This adapter accepts the following options:
     *
     * - mh: An optional curl_multi resource
     * - handle_factory: An optional callable used to generate curl handle
     *   resources. the callable accepts a request hash and returns an array
     *   of the handle, headers file resource, and the body resource.
     * - select_timeout: Optional timeout (in seconds) to block before timing
     *   out while selecting curl handles. Defaults to 1 second.
     * - max_handles: Optional integer representing the maximum number of
     *   open requests. When this number is reached, the queued futures are
     *   flushed.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->mh = isset($options['mh'])
            ? $options['mh']
            : curl_multi_init();
        $this->factory = isset($options['handle_factory'])
            ? $options['handle_factory']
            : new CurlFactory();
        $this->selectTimeout = isset($options['select_timeout'])
            ? $options['select_timeout']
            : 1;
        $this->maxHandles = isset($options['max_handles'])
            ? $options['max_handles']
            : 100;
    }

    public function __destruct()
    {
        // Finish any open futures before terminating the script.
        // Be sure to shift off the array to account for subsequent futures
        // being added while destructing.
        while ($future = array_shift($this->futures)) {
            $future->deref();
        }

        if ($this->mh) {
            curl_multi_close($this->mh);
            $this->mh = null;
        }
    }

    public function __invoke(array $request)
    {
        $factory = $this->factory;
        // Ensure headers are by reference. They're updated elsewhere.
        $result = $factory($request);
        $handle = $result[0];
        $headers =& $result[1];
        $body = $result[2];

        $atom = null;
        $future = new Future(
            function () use ($request, &$headers, $body, $handle, &$atom) {
                if (!$atom) {
                    $atom = $this->getFutureResult($headers, $body, $handle);
                    if (isset($request['then'])) {
                        $then = $request['then'];
                        $atom = $then($atom) ?: $atom;
                    }
                }
                return $atom;
            }
        );

        $this->futures[(int) $handle] = $future;
        $this->addRequest($request, $handle);

        if (count($this->futures) >= $this->maxHandles) {
            $future->deref();
        }

        return $future;
    }

    private function addRequest(array $request, $handle)
    {
        $this->handles[(int) $handle] = [$handle, &$request, []];
        curl_multi_add_handle($this->mh, $handle);

        $future = empty($request['future']) ? false : $request['future'];

        // "batch" futures are only sent once the pool has many requests.
        if ($future !== 'batch') {
            do {
                $mrc = curl_multi_exec($this->mh, $this->active);
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);
            $this->processMessages();
        }
    }

    private function removeProcessed($id)
    {
        if (isset($this->processed[$id])) {
            curl_multi_remove_handle($this->mh, $this->processed[$id][0]);
            curl_close($this->processed[$id][0]);
            unset($this->processed[$id]);
        }
    }

    private function execute()
    {
        do {
            if ($this->active &&
                curl_multi_select($this->mh, $this->selectTimeout) === -1
            ) {
                // Perform a usleep if a select returns -1.
                // See: https://bugs.php.net/bug.php?id=61141
                usleep(250);
            }
            do {
                $mrc = curl_multi_exec($this->mh, $this->active);
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);
            $this->processMessages();
        } while ($this->handles || $this->active);
    }

    private function processMessages()
    {
        while ($done = curl_multi_info_read($this->mh)) {
            $id = (int) $done['handle'];
            $trans =& $this->handles[$id];
            $trans[2]['transfer_stats'] = curl_getinfo($done['handle']);

            if ($done['result'] !== CURLM_OK) {
                $trans[2]['curl']['errno'] = $done['result'];
                if (function_exists('curl_strerror')) {
                    $trans[2]['curl']['error'] = curl_strerror($done['result']);
                }
            }

            $this->processed[$id] = $trans;
            unset($this->handles[$id]);

            // Trigger future responses immediately if needed.
            if (isset($this->futures[$id])) {
                $future = $this->futures[$id];
                unset($this->futures[$id]);
                $future->deref();
            }
        }
    }

    private function getFutureResult(&$headers, $body, $handle)
    {
        $id = (int) $handle;
        unset($this->futures[$id]);
        if (!isset($this->processed[$id])) {
            $this->execute();
        }
        $result = $this->processed[$id];
        $this->removeProcessed($id);

        return CurlFactory::createResponse($result[2], $headers, $body);
    }
}
