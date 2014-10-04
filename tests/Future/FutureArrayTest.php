<?php
namespace GuzzleHttp\Tests\Ring\Future;

use GuzzleHttp\Ring\Future\FutureArray;
use GuzzleHttp\Ring\ValidatedDeferredType;

class FutureArrayTest extends \PHPUnit_Framework_TestCase
{
    public function testLazilyCallsDeref()
    {
        $c = false;
        $deferred = new ValidatedDeferredType('array');
        $f = new FutureArray(
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
        $deferred = new ValidatedDeferredType('array');
        $f = new FutureArray(
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
        $deferred = new ValidatedDeferredType('array');
        $f = new FutureArray($deferred->promise(), function () {});
        $f->foo;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected the resolved value to be of type "array", but got string(4) "foo!"
     */
    public function testValidatesDerefFunction()
    {
        $deferred = new ValidatedDeferredType('array');
        $f = new FutureArray(
            $deferred->promise(),
            function () use (&$c, $deferred) {
                $deferred->resolve('foo!');
            }
        );
        $f->deref();
    }
}
