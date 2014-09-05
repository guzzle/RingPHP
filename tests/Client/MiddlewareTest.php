<?php
namespace GuzzleHttp\Tests\Ring\Client;

use GuzzleHttp\Ring\Client\Middleware;
use GuzzleHttp\Ring\Future;

class MiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function testFutureCallsDefaultAdapter()
    {
        $future = new Future(function () { return []; });
        $calledA = false;
        $a = function (array $req) use (&$calledA, $future) {
            $calledA = true;
            return $future;
        };
        $calledB = false;
        $b = function (array $req) use (&$calledB) { $calledB = true; };
        $s = Middleware::wrapFuture($a, $b);
        $result = $s([]);
        $this->assertTrue($calledA);
        $this->assertFalse($calledB);
        $this->assertInternalType('array', $result);
    }

    public function testFutureCallsStreamingAdapter()
    {
        $future = new Future(function () {});
        $calledA = false;
        $a = function (array $req) use (&$calledA) { $calledA = true; };
        $calledB = false;
        $b = function (array $req) use (&$calledB, $future) {
            $calledB = true;
            return $future;
        };
        $s = Middleware::wrapFuture($a, $b);
        $result = $s(['client' => ['future' => true]]);
        $this->assertFalse($calledA);
        $this->assertTrue($calledB);
        $this->assertSame($future, $result);
    }

    public function testStreamingCallsDefaultAdapter()
    {
        $calledA = false;
        $a = function (array $req) use (&$calledA) { $calledA = true; };
        $calledB = false;
        $b = function (array $req) use (&$calledB) { $calledB = true; };
        $s = Middleware::wrapStreaming($a, $b);
        $s([]);
        $this->assertTrue($calledA);
        $this->assertFalse($calledB);
    }

    public function testStreamingCallsStreamingAdapter()
    {
        $calledA = false;
        $a = function (array $req) use (&$calledA) { $calledA = true; };
        $calledB = false;
        $b = function (array $req) use (&$calledB) { $calledB = true; };
        $s = Middleware::wrapStreaming($a, $b);
        $s(['client' => ['stream' => true]]);
        $this->assertFalse($calledA);
        $this->assertTrue($calledB);
    }

    public function testSynchronousForcesSynchronousResponses()
    {
        $h = Middleware::wrapSynchronous(function () {
            return new Future(function () {
                return ['status' => 200];
            });
        });

        $this->assertEquals(['status' => 200], $h([]));
    }

    public function testSynchronousReturnsRegularResponses()
    {
        $h = Middleware::wrapSynchronous(function () {
            return ['status' => 200];
        });
        $this->assertEquals(['status' => 200], $h([]));
    }
}
