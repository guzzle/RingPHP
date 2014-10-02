<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\RingFuture;
use GuzzleHttp\Ring\ValidatedDeferred;

class RingFutureTest extends \PHPUnit_Framework_TestCase
{
    public function testLazilyCallsDeref()
    {
        $c = false;
        $deferred = ValidatedDeferred::deferredArray();
        $f = new RingFuture(
            $deferred->promise(),
            function () use (&$c, $deferred) {
                $c = true;
                $deferred->resolve(['status' => 200]);
            }
        );
        $this->assertFalse($c);
        $this->assertFalse($f->realized());
        $this->assertEquals(200, $f['status']);
        $this->assertTrue($c);
    }

    public function testActsLikeArray()
    {
        $deferred = ValidatedDeferred::deferredArray();
        $f = new RingFuture(
            $deferred->promise(),
            function () use (&$c, $deferred) {
                $deferred->resolve(['status' => 200]);
            }
        );

        $this->assertTrue(isset($f['status']));
        $this->assertEquals(200, $f['status']);
        $this->assertEquals(['status' => 200], $f->deref());
        $this->assertEquals(1, count($f));
        $f['baz'] = 10;
        $this->assertEquals(10, $f['baz']);
        unset($f['baz']);
        $this->assertFalse(isset($f['baz']));
        $this->assertEquals(['status' => 200], iterator_to_array($f));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowsWhenAccessingInvalidProperty()
    {
        $deferred = ValidatedDeferred::deferredArray();
        $f = new RingFuture($deferred->promise(), function () {});
        $f->foo;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected array. Got
     */
    public function testValidatesDerefFunction()
    {
        $deferred = ValidatedDeferred::deferredArray();
        $f = new RingFuture(
            $deferred->promise(),
            function () use (&$c, $deferred) {
                $deferred->resolve('foo!');
            }
        );
        $f->deref();
    }
}
