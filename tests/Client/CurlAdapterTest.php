<?php
namespace GuzzleHttp\Tests\Ring\Client;

use GuzzleHttp\Ring\Client\CurlAdapter;
use GuzzleHttp\Stream\FnStream;
use GuzzleHttp\Stream\Stream;

class CurlAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!function_exists('curl_reset')) {
            $this->markTestSkipped('curl_reset() is not available');
        }
    }

    protected function getAdapter($factory = null, $options = [])
    {
        return new CurlAdapter($options);
    }

    public function testCanSetMaxHandles()
    {
        $a = new CurlAdapter(['max_handles' => 10]);
        $this->assertEquals(10, $this->readAttribute($a, 'maxHandles'));
    }

    public function testCreatesCurlErrors()
    {
        $adapter = new CurlAdapter();
        $response = $adapter([
            'http_method' => 'GET',
            'uri' => '/',
            'headers' => ['host' => ['localhost:123']],
            'client' => ['timeout' => 0.001, 'connect_timeout' => 0.001]
        ]);
        $this->assertNull($response['status']);
        $this->assertNull($response['reason']);
        $this->assertEquals([], $response['headers']);
        $this->assertInstanceOf(
            'GuzzleHttp\Ring\Exception\RingException',
            $response['error']
        );

        $this->assertEquals(
            1,
            preg_match('/^cURL error \d+: .*$/', $response['error']->getMessage())
        );
    }

    public function testReleasesAdditionalEasyHandles()
    {
        Server::flush();
        $response = [
            'status'  => 200,
            'headers' => ['Content-Length' => [4]],
            'body'    => 'test'
        ];

        Server::enqueue([$response, $response, $response, $response]);
        $a = new CurlAdapter(['max_handles' => 2]);

        $fn = function () use (&$calls, $a, &$fn) {
            if (++$calls < 4) {
                $a([
                    'http_method' => 'GET',
                    'headers'     => ['host' => [Server::$host]],
                    'client'      => ['progress' => $fn]
                ]);
            }
        };

        $request = [
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]],
            'client'      => [
                'progress' => $fn
            ]
        ];

        $a($request);
        $this->assertCount(2, $this->readAttribute($a, 'handles'));
    }

    public function testReusesHandles()
    {
        Server::flush();
        $response = ['status' => 200];
        Server::enqueue([$response, $response]);
        $a = new CurlAdapter();
        $request = [
            'http_method' => 'GET',
            'headers'     => ['host' => [Server::$host]]
        ];
        $a($request);
        $a($request);
    }

    public function testCallsThenOnComplete()
    {
        Server::flush();
        Server::enqueue([['status' => 200, 'reason' => 'OK']]);
        $res = null;
        $adapter = new CurlAdapter();
        $adapter([
            'http_method' => 'GET',
            'headers' => ['host' => [Server::$host]],
            'then' => function (array $response) use (&$res) {
                $res = $response;
            }
        ]);
        $this->assertInternalType('array', $res);
        $this->assertEquals(200, $res['status']);
        $this->assertEquals('OK', $res['reason']);
    }

    public function testHasNoMemoryLeaks()
    {
        Server::flush();
        $response = ['status' => 200];
        Server::enqueue(array_fill_keys(range(0, 25), $response));
        $a = new CurlAdapter();
        $memory = [];
        for ($i = 0; $i < 25; $i++) {
            $request = [
                'http_method' => 'GET',
                'headers'     => ['host' => [Server::$host]],
                'then'        => function () {},
                'progress'    => function () {},
                'client'      => [
                    'save_to' => FnStream::decorate(Stream::factory(), [
                        'write' => function ($str) use (&$request) {
                            return strlen($str);
                        }
                    ])
                ]
            ];
            $a($request);
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
}
