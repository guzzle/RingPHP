<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\RingFuture;

class RingFutureTest extends \PHPUnit_Framework_TestCase
{
    public function testLazilyCallsDeref()
    {
        $c = false;
        $f = new RingFuture(function () use (&$c) {
            $c = true;
            return ['status' => 200];
        });
        $this->assertFalse($c);
        $this->assertEquals(200, $f['status']);
        $this->assertTrue($c);
    }

    public function testActsLikeArray()
    {
        $f = new RingFuture(function () {
            return ['status' => 200];
        });

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
        $f = new RingFuture(function () {});
        $f->foo;
    }

    public function testCanCancelRingFuture()
    {
        $called = [];
        $f = new RingFuture(function () use (&$called) {
            $called[] = 'deref';
            return ['status' => 200];
        }, function () use (&$called) {
            $called[] = 'cancel';
            return true;
        });
        $this->assertTrue($f->cancel());
        $this->assertEquals(['cancel'], $called);
        $this->assertTrue($f->cancelled());
        $this->assertFalse($f->cancel());
        $this->assertFalse($f->realized());
    }

    public function testCancellingCompletedRingFutureReturnsFalse()
    {
        $called = [];
        $f = new RingFuture(function () use (&$called) {
            $called[] = 'deref';
            return ['status' => 200];
        }, function () use (&$called) {
            $called[] = 'cancel';
            return true;
        });
        $f->deref();
        $this->assertFalse($f->cancel());
        $this->assertEquals(['status' => 200], $f->deref());
        $this->assertEquals(['deref'], $called);
        $this->assertFalse($f->cancelled());
        $this->assertTrue($f->realized());
    }

    public function testCancellingWithNoCancelFunctionPreventsDeref()
    {
        $called = [];
        $f = new RingFuture(function () use (&$called) {
            $called[] = 'deref';
            return ['status' => 200];
        });
        $this->assertFalse($f->cancel());
        $this->assertTrue($f->cancelled());
        $this->assertEquals([], $called);
        $this->assertFalse($f->realized());
    }

    /**
     * @expectedException \GuzzleHttp\Ring\Exception\CancelledFutureAccessException
     */
    public function testAccessingCancelledResponseRaisesException()
    {
        $f = new RingFuture(function () {});
        $f->cancel();
        $f['status'];
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testValidatesDerefFunction()
    {
        $f = new RingFuture(function () {
            return 'foo!';
        });
        $f->deref();
    }
}
