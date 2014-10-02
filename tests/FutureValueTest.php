<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\FutureValue;
use React\Promise\Deferred;

class FutureValueTest extends \PHPUnit_Framework_TestCase
{
    public function testDerefReturnsValue()
    {
        $called = 0;
        $deferred = new Deferred();

        $f = new FutureValue(
            $deferred->promise(),
            function () use ($deferred, &$called) {
                $called++;
                $deferred->resolve('foo');
            }
        );

        $this->assertEquals('foo', $f->deref());
        $this->assertEquals(1, $called);
        $this->assertEquals('foo', $f->deref());
        $this->assertEquals(1, $called);
        $this->assertFalse($f->cancelled());
        $this->assertFalse($f->cancel());
        $this->assertTrue($f->realized());
    }

    /**
     * @expectedException \GuzzleHttp\Ring\Exception\CancelledFutureAccessException
     */
    public function testThrowsWhenAccessingCancelled()
    {
        $f = new FutureValue(
            (new Deferred())->promise(),
            function () {},
            function () { return true; }
        );
        $this->assertTrue($f->cancel());
        $f->deref();
    }

    public function testThenReturnsNewFuturevalue()
    {
        $called = 0;
        $deferred = new Deferred();
        $f = (new FutureValue(
            $deferred->promise(),
            function () use ($deferred, &$called) {
                $deferred->resolve('foo');
                $called++;
            }
        ))->then(function ($value) {
            return $value . 'bar';
        });

        $this->assertInstanceOf('GuzzleHttp\Ring\FutureValue', $f);
        $this->assertEquals('foobar', $f->deref());
        $this->assertEquals(1, $called);
        $this->assertTrue($f->realized());
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testThrowsWhenDerefFailure()
    {
        $called = false;
        $deferred = new Deferred();
        $f = new FutureValue(
            $deferred->promise(),
            function () use(&$called) {
                $called = true;
            }
        );
        $deferred->reject(new \OutOfBoundsException());
        $f->deref();
        $this->assertFalse($called);
    }
}
