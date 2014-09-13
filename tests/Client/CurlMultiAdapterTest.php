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
        $this->assertEquals(200, $response['status']);
        $this->assertSame($res, $response->deref());
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
        $this->assertInstanceOf('GuzzleHttp\Ring\Future', $response);
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
            'http_method' => 'PUT',
            'headers'     => ['host' => [Server::$host]],
            'future'      => 'batch' // passing this to control the test
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

    public function testHasNoMemoryLeaks()
    {
        Server::flush();
        $response = ['status' => 200];
        Server::enqueue(array_fill_keys(range(0, 25), $response));
        $a = new CurlMultiAdapter(['max_handles' => 5]);
        $memory = [];
        for ($i = 0; $i < 25; $i++) {
            $a([
                'http_method' => 'GET',
                'headers'     => ['host' => [Server::$host]]
            ]);
            $request['then'] = function () use ($i, &$called) {};
            $memory[] = memory_get_usage(true);
        }
        $this->assertCount(25, Server::received());
        // Take the last 15 entries and ensure they are consistent
        $last = $memory[9];
        $entries = array_slice($memory, 10);
        foreach ($entries as $entry) {
            $this->assertEquals($last, $entry);
            $last = $entry;
        }
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
                'future'      => 'batch'
            ]);
            $this->assertTrue($response->cancel());
            $responses[] = $response;
        }

        $this->assertCount(0, Server::received());

        foreach ($responses as $response) {
            $this->assertTrue($response->cancelled());
            $this->assertFalse($response->realized());
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
}
