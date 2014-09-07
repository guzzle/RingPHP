<?php
namespace GuzzleHttp\Tests\Ring\Client;

use GuzzleHttp\Ring\Client\CurlMultiAdapter;

class CurlMultiAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testSendsRequest()
    {
        Server::enqueue([['status' => 200]]);
        $a = new CurlMultiAdapter();
        $res = null;
        $response = $a([
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]],
            'then'        => function (array $response) use (&$res) {
                $res = $response;
            }
        ]);
        $this->assertInstanceOf('GuzzleHttp\Ring\Future', $response);
        $this->assertNull($res);
        $this->assertEquals(200, $response['status']);
        $this->assertSame($res, $response->deref());
        $this->assertArrayHasKey('transfer_stats', $response);
        $realUrl = trim($response['transfer_stats']['url'], '/');
        $this->assertEquals(trim(Server::$url, '/'), $realUrl);
        $this->assertArrayHasKey('effective_url', $response);
        $this->assertEquals(Server::$url, $response['effective_url']);
    }

    public function testCreatesErrorResponses()
    {
        $url = 'http://localhost:123/';
        $a = new CurlMultiAdapter();
        $response = $a([
            'http_method' => 'GET',
            'headers'     => ['host' => ['localhost:123']]
        ]);
        $this->assertInstanceOf('GuzzleHttp\Ring\Future', $response);
        $this->assertNull($response['status']);
        $this->assertNull($response['reason']);
        $this->assertEquals([], $response['headers']);
        $this->assertArrayHasKey('error', $response);
        $this->assertContains('cURL error ', $response['error']->getMessage());
        $this->assertArrayHasKey('transfer_stats', $response);
        $this->assertEquals($url, $response['transfer_stats']['url']);
        $this->assertArrayHasKey('effective_url', $response);
        $this->assertEquals($url, $response['effective_url']);
    }

    public function testSendsFuturesWhenDestructed()
    {
        Server::enqueue([['status' => 200]]);
        $a = new CurlMultiAdapter();
        $response = $a([
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]]
        ]);
        $this->assertInstanceOf('GuzzleHttp\Ring\Future', $response);
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
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]]
        ];
        $response = ['status' => 200];
        Server::flush();
        Server::enqueue([$response, $response, $response]);
        $a = new CurlMultiAdapter(['max_handles' => 3]);
        $called = $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $request['then'] = function () use ($i, &$called) {
                $called[$i] = $i;
            };
            $responses[] = $a($request);
        }
        $this->assertCount(3, Server::received());
        $this->assertEquals([0, 1, 2], $called);
        $responses[3]->cancel();
        $responses[4]->cancel();
    }
}
