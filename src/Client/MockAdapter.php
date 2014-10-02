<?php
namespace GuzzleHttp\Ring\Client;

use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\RingFuture;
use GuzzleHttp\Ring\ArrayFutureInterface;

/**
 * Ring adapter that returns a canned response or evaluated function result.
 */
class MockAdapter
{
    /** @var callable|array|ArrayFutureInterface */
    private $result;

    /**
     * Provide an array or future to always return the same value. Provide a
     * callable that accepts a request object and returns an array or future
     * to dynamically create a response.
     *
     * @param array|RingFuture|callable $result Mock return value.
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

        if (is_array($response)) {
            $response = Core::createResolvedRingResponse($response + [
                'status'        => null,
                'body'          => null,
                'headers'       => [],
                'reason'        => null,
                'effective_url' => null
            ]);
        } elseif (!$response instanceof RingFuture) {
            throw new \InvalidArgumentException(
                'Response must be an array or RingFuture'
            );
        }

        return $response;
    }
}
