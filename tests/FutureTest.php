<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\Future;

class FutureTest extends \PHPUnit_Framework_TestCase
{
    public function testLazilyCallsDeref()
    {
        $c = false;
        $f = new Future(function () use (&$c) {
            $c = true;
            return ['status' => 200];
        });
        $this->assertFalse($c);
        $this->assertEquals(200, $f['status']);
        $this->assertTrue($c);
    }

    public function testActsLikeArray()
    {
        $f = new Future(function () {
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
        $f = new Future(function () {});
        $f->foo;
    }

    public function testCanCancelFuture()
    {
        $called = false;
        $f = new Future(function () use (&$called) {
            $called[] = 'deref';
            return ['status' => 200];
        }, function () use (&$called) {
            $called[] = 'cancel';
            return true;
        });
        $this->assertTrue($f->cancel());
        $this->assertEquals([], $f->deref());
        $this->assertEquals(['cancel'], $called);
        $this->assertArrayNotHasKey('status', $f);
        $this->assertTrue($f->cancelled());
        $this->assertFalse($f->cancel());
        $this->assertFalse($f->dereferenced());
    }
}
