<?php
namespace GuzzleHttp\Tests\Ring\Future;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;
use GuzzleHttp\Ring\Future\CompletedFutureValue;

class CompletedFutureValueTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsValue()
    {
        $f = new CompletedFutureValue('hi');
        $this->assertTrue($f->realized());
        $this->assertEquals('hi', $f->deref());
        $this->assertFalse($f->cancel());
        $this->assertFalse($f->cancelled());

        $a = null;
        $f->then(function ($v) use (&$a) {
            $a = $v;
        });
        $this->assertSame('hi', $a);
    }

    public function testThrows()
    {
        $ex = new \Exception('foo');
        $f = new CompletedFutureValue(null, $ex);
        $this->assertFalse($f->cancel());
        $this->assertFalse($f->cancelled());
        $this->assertTrue($f->realized());
        try {
            $f->deref();
            $this->fail('did not throw');
        } catch (\Exception $e) {
            $this->assertSame($e, $ex);
        }
    }

    public function testMarksAsCancelled()
    {
        $ex = new CancelledFutureAccessException();
        $f = new CompletedFutureValue(null, $ex);
        $this->assertTrue($f->cancelled());
        $this->assertTrue($f->realized());
        try {
            $f->deref();
            $this->fail('did not throw');
        } catch (\Exception $e) {
            $this->assertSame($e, $ex);
        }
    }
}
