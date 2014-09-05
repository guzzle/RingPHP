<?php
namespace GuzzleHttp\Tests\Ring\Client;

use GuzzleHttp\Ring\Client\Middleware\FutureProxy;
use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\Future;

class FutureProxyTest extends \PHPUnit_Framework_TestCase
{
    public function testCallsDefaultAdapter()
    {
        $future = new Future(function () { return []; });
        $calledA = false;
        $a = function (array $req) use (&$calledA, $future) {
            $calledA = true;
            return $future;
        };
        $calledB = false;
        $b = function (array $req) use (&$calledB) { $calledB = true; };
        $s = FutureProxy::wrap($a, $b);
        $result = $s([]);
        $this->assertTrue($calledA);
        $this->assertFalse($calledB);
        $this->assertInternalType('array', $result);
    }

    public function testCallsStreamingAdapter()
    {
        $future = new Future(function () {});
        $calledA = false;
        $a = function (array $req) use (&$calledA) { $calledA = true; };
        $calledB = false;
        $b = function (array $req) use (&$calledB, $future) {
            $calledB = true;
            return $future;
        };
        $s = FutureProxy::wrap($a, $b);
        $result = $s(['client' => ['future' => true]]);
        $this->assertFalse($calledA);
        $this->assertTrue($calledB);
        $this->assertSame($future, $result);
    }
}
