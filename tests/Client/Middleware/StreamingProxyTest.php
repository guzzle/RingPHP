<?php
namespace GuzzleHttp\Tests\Ring\Client;

use GuzzleHttp\Ring\Client\Middleware\StreamingProxy;

class StreamingProxyTest extends \PHPUnit_Framework_TestCase
{
    public function testCallsDefaultAdapter()
    {
        $calledA = false;
        $a = function (array $req) use (&$calledA) { $calledA = true; };
        $calledB = false;
        $b = function (array $req) use (&$calledB) { $calledB = true; };
        $s = StreamingProxy::wrap($a, $b);
        $s([]);
        $this->assertTrue($calledA);
        $this->assertFalse($calledB);
    }

    public function testCallsStreamingAdapter()
    {
        $calledA = false;
        $a = function (array $req) use (&$calledA) { $calledA = true; };
        $calledB = false;
        $b = function (array $req) use (&$calledB) { $calledB = true; };
        $s = StreamingProxy::wrap($a, $b);
        $s(['client' => ['stream' => true]]);
        $this->assertFalse($calledA);
        $this->assertTrue($calledB);
    }
}
