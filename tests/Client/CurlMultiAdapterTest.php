<?php
namespace GuzzleHttp\Tests\Ring\Client;

use GuzzleHttp\Ring\Client\CurlMultiAdapter;

class CurlMultiAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testSendsRequest()
    {
        Server::enqueue([['status' => 200]]);
        $a = new CurlMultiAdapter();
        $response = $a([
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]]
        ]);
        $this->assertInstanceOf('GuzzleHttp\Ring\FutureArray', $response);
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('transfer_stats', $response);
        $realUrl = trim($response['transfer_stats']['url'], '/');
        $this->assertEquals(trim(Server::$url, '/'), $realUrl);
        $this->assertArrayHasKey('effective_url', $response);
        $this->assertEquals(
            trim(Server::$url, '/'),
            trim($response['effective_url'], '/')
        );
    }

    public function testCreatesErrorResponses()
    {
        $url = 'http://localhost:123/';
        $a = new CurlMultiAdapter();
        $response = $a([
            'http_method' => 'GET',
            'headers'     => ['host' => ['localhost:123']]
        ]);
        $this->assertInstanceOf('GuzzleHttp\Ring\FutureArray', $response);
        $this->assertNull($response['status']);
        $this->assertNull($response['reason']);
        $this->assertEquals([], $response['headers']);
        $this->assertArrayHasKey('error', $response);
        $this->assertContains('cURL error ', $response['error']->getMessage());
        $this->assertArrayHasKey('transfer_stats', $response);
        $this->assertEquals(
            trim($url, '/'),
            trim($response['transfer_stats']['url'], '/')
        );
        $this->assertArrayHasKey('effective_url', $response);
        $this->assertEquals(
            trim($url, '/'),
            trim($response['effective_url'], '/')
        );
    }

    public function testSendsFuturesWhenDestructed()
    {
        Server::enqueue([['status' => 200]]);
        $a = new CurlMultiAdapter();
        $response = $a([
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]]
        ]);
        $this->assertInstanceOf('GuzzleHttp\Ring\FutureArray', $response);
        $a->__destruct();
        $this->assertEquals(200, $response['status']);
    }

    public function testCanSetMaxHandles()
    {
        $a = new CurlMultiAdapter(['max_handles' => 2]);
        $this->assertEquals(2, $this->readAttribute($a, 'maxHandles'));
    }

    public function testCanSetSelectTimeout()
    {
        $a = new CurlMultiAdapter(['select_timeout' => 2]);
        $this->assertEquals(2, $this->readAttribute($a, 'selectTimeout'));
    }

    public function testSendsFuturesWhenMaxHandlesIsReached()
    {
        $request = [
            'http_method' => 'PUT',
            'headers'     => ['host' => [Server::$host]],
            'future'      => 'lazy' // passing this to control the test
        ];
        $response = ['status' => 200];
        Server::flush();
        Server::enqueue([$response, $response, $response]);
        $a = new CurlMultiAdapter(['max_handles' => 3]);
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $a($request);
        }
        $this->assertCount(3, Server::received());
        $responses[3]->cancel();
        $responses[4]->cancel();
    }

    public function testCanCancel()
    {
        Server::flush();
        $response = ['status' => 200];
        Server::enqueue(array_fill_keys(range(0, 10), $response));
        $a = new CurlMultiAdapter();
        $responses = [];

        for ($i = 0; $i < 10; $i++) {
            $response = $a([
                'http_method' => 'GET',
                'headers'     => ['host' => [Server::$host]],
                'future'      => 'lazy'
            ]);
            $this->assertTrue($response->cancel());
            $responses[] = $response;
        }

        $this->assertCount(0, Server::received());

        foreach ($responses as $response) {
            $this->assertTrue($response->cancelled());
            $this->assertTrue($response->realized());
        }
    }

    public function testCannotCancelFinished()
    {
        Server::flush();
        Server::enqueue([['status' => 200]]);
        $a = new CurlMultiAdapter();
        $response = $a([
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]]
        ]);
        $response->deref();
        $this->assertFalse($response->cancel());
    }

    public function testDelaysInParallel()
    {
        Server::flush();
        Server::enqueue([['status' => 200]]);
        $a = new CurlMultiAdapter();
        $expected = microtime(true) + (100 / 1000);
        $response = $a([
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]],
            'client'      => ['delay' => 100]
        ]);
        $response->deref();
        $this->assertGreaterThanOrEqual($expected, microtime(true));
    }

    public function testReturnsRealizedWhenReadyButNotDereferenced()
    {
        Server::flush();
        Server::enqueue([['status' => 200]]);
        $a = new CurlMultiAdapter();
        $response = $a([
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]]
        ]);
        $ref = new \ReflectionMethod($a, 'execute');
        $ref->setAccessible(true);
        $ref->invoke($a);
        $this->assertTrue($response->realized());
    }
}
