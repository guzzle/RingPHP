<?php
namespace GuzzleHttp\Tests\Ring\Client;

use GuzzleHttp\Ring\Client\MockAdapter;
use GuzzleHttp\Ring\RingFuture;
use GuzzleHttp\Ring\ValidatedDeferred;

class MockAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsArray()
    {
        $mock = new MockAdapter(['status' => 200]);
        $response = $mock([]);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals([], $response['headers']);
        $this->assertNull($response['body']);
        $this->assertNull($response['reason']);
        $this->assertNull($response['effective_url']);
    }

    public function testReturnsFutures()
    {
        $deferred = ValidatedDeferred::deferredArray();
        $future = new RingFuture(
            $deferred->promise(),
            function () use ($deferred) {
                $deferred->resolve(['status' => 200]);
            }
        );
        $mock = new MockAdapter($future);
        $response = $mock([]);
        $this->assertInstanceOf('GuzzleHttp\Ring\RingFuture', $response);
        $this->assertEquals(200, $response['status']);
    }

    public function testReturnsFuturesWithThenCall()
    {
        $deferred = ValidatedDeferred::deferredArray();
        $future = new RingFuture(
            $deferred->promise(),
            function () use ($deferred) {
                $deferred->resolve(['status' => 200]);
            }
        );
        $mock = new MockAdapter($future);
        $response = $mock([])->then(function ($value) {
            $value['status'] = 304;
            return $value;
        });
        $this->assertInstanceOf('GuzzleHttp\Ring\RingFuture', $response);
        $this->assertEquals(304, $response['status']);
    }

    public function testReturnsFuturesAndProxiesCancel()
    {
        $c = null;
        $deferred = ValidatedDeferred::deferredArray();
        $future = new RingFuture(
            $deferred->promise(),
            function () {},
            function () use (&$c) {
                $c = true;
                return true;
            }
        );
        $mock = new MockAdapter($future);
        $response = $mock([]);
        $this->assertInstanceOf('GuzzleHttp\Ring\RingFuture', $response);
        $this->assertTrue($response->cancel());
        $this->assertTrue($c);
    }
}
