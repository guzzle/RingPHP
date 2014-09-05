<?php
namespace GuzzleHttp\Tests\Ring\Client;

use GuzzleHttp\Ring\Client\Middleware\Synchronous;
use GuzzleHttp\Ring\Future;

class SynchronousTest extends \PHPUnit_Framework_TestCase
{
    public function testForcesSynchronousResponses()
    {
        $h = Synchronous::wrap(function () {
            return new Future(function () {
                return ['status' => 200];
            });
        });

        $this->assertEquals(['status' => 200], $h([]));
    }

    public function testReturnsRegularResponses()
    {
        $h = Synchronous::wrap(function () {
            return ['status' => 200];
        });
        $this->assertEquals(['status' => 200], $h([]));
    }
}
