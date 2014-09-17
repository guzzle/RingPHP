<?php
namespace GuzzleHttp\Tests\Ring\Client;

use GuzzleHttp\Ring\Client\MockAdapter;
use GuzzleHttp\Ring\Future;

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

    public function testCallsThenFromArrayResponses()
    {
        $mock = new MockAdapter(['status' => 200]);
        $response = $mock([
            'then' => function (array &$response) {
                $this->assertEquals(200, $response['status']);
                $response = ['status' => 304];
            }
        ]);
        $this->assertEquals(304, $response['status']);
        $this->assertEquals([], $response['headers']);
        $this->assertNull($response['body']);
        $this->assertNull($response['reason']);
        $this->assertNull($response['effective_url']);
    }

    public function testReturnsFutures()
    {
        $future = new Future(function () {
            return ['status' => 200];
        });
        $mock = new MockAdapter($future);
        $response = $mock([]);
        $this->assertInstanceOf('GuzzleHttp\Ring\Future', $response);
        $this->assertEquals(200, $response['status']);
    }

    public function testReturnsFuturesWithThenCall()
    {
        $future = new Future(function () {
            return ['status' => 200];
        });
        $mock = new MockAdapter($future);
        $response = $mock([
            'then' => function (array &$response) {
                $this->assertEquals(200, $response['status']);
                $response = ['status' => 304];
            }
        ]);
        $this->assertInstanceOf('GuzzleHttp\Ring\Future', $response);
        $this->assertEquals(304, $response['status']);
    }

    public function testReturnsFuturesAndProxiesCancel()
    {
        $c = null;
        $future = new Future(function () {}, function () use (&$c) {
            $c = true;
            return true;
        });
        $mock = new MockAdapter($future);
        $response = $mock([
            'then' => function (array $response) {}
        ]);
        $this->assertInstanceOf('GuzzleHttp\Ring\Future', $response);
        $this->assertTrue($response->cancel());
        $this->assertTrue($c);
    }
}
