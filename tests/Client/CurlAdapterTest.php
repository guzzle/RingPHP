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

    public function testHasNoMemoryLeaks()
    {
        Server::flush();
        $response = ['status' => 200];
        Server::enqueue(array_fill_keys(range(0, 100), $response));
        $a = new CurlAdapter();
        $memory = [];

        for ($i = 0; $i < 100; $i++) {
            $a([
                'http_method' => 'GET',
                'headers'     => ['host' => [Server::$host]],
                'client'      => [
                    'save_to' => FnStream::decorate(Stream::factory(), [
                        'write' => function ($str) {
                            return strlen($str);
                        }
                    ])
                ]
            ]);
            $memory[] = memory_get_usage(true);
        }

        $this->assertCount(100, Server::received());
        // Take the last 50 entries and ensure they are consistent
        $last = $memory[9];
        $entries = array_slice($memory, 50);

        foreach ($entries as $entry) {
            $this->assertEquals($last, $entry);
            $last = $entry;
        }
    }
}
