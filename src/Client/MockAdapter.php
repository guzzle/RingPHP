<?php
namespace GuzzleHttp\Ring\Client;

use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\Future;
use GuzzleHttp\Ring\RingFutureInterface;

/**
 * Ring adapter that returns a canned response or evaluated function result.
 *
 * This class is useful for implementing mock responses while still accounting
 * for things like the "then" request option.
 */
class MockAdapter
{
    /** @var callable|array|RingFutureInterface */
    private $result;

    /**
     * Provide an array or future to always return the same value. Provide a
     * callable that accepts a request object and returns an array or future
     * to dynamically create a response.
     *
     * @param array|RingFutureInterface|callable $result Mock return value.
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    public function __invoke(array $request)
    {
        Core::doSleep($request);
        $response = is_callable($this->result)
            ? call_user_func($this->result, $request)
            : $this->result;

        if (isset($request['then'])) {
            // Create a new future that will call "then" when deref'd
            if ($response instanceof RingFutureInterface) {
                $response = new Future(function () use ($request, $response) {
                    return $this->callThen($request, Core::deref($response));
                }, function () use ($response) {
                    return $response->cancel();
                });
            } else {
                $response = $this->callThen($request, $response);
            }
        } elseif (is_array($response)) {
            return $this->addMissing($response);
        }

        return $response;
    }

    private function callThen(array $request, $response)
    {
        Core::callThen($request, $response);
        if (is_array($response)) {
            $response = $this->addMissing($response);
        }

        return $response;
    }

    private function addMissing(array $response)
    {
        return $response + [
            'status'        => null,
            'body'          => null,
            'headers'       => [],
            'reason'        => null,
            'effective_url' => null
        ];
    }
}
