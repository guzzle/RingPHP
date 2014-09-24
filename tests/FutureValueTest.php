<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\FutureValue;

class FutureValueTest extends \PHPUnit_Framework_TestCase
{
    public function testDerefReturnsValue()
    {
        $called = 0;
        $f = new FutureValue(function () use (&$called) {
            $called++;
            return 'foo';
        });
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
        $f = new FutureValue(function () {}, function () { return true; });
        $this->assertTrue($f->cancel());
        $f->deref();
    }
}
