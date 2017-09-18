<?php
namespace GuzzleHttp\Tests\Ring\Future;

use GuzzleHttp\Ring\Future\FutureArray;
use React\Promise\Deferred;

class FutureArrayTest extends \PHPUnit_Framework_TestCase
{
    public function testLazilyCallsDeref()
    {
        $c = false;
        $deferred = new Deferred();
        $f = new FutureArray(
            $deferred->promise(),
            function () use (&$c, $deferred) {
                $c = true;
                $deferred->resolve(['status' => 200]);
            }
        );
        $this->assertFalse($c);
        $this->assertFalse($this->readAttribute($f, 'isRealized'));
        $this->assertEquals(200, $f['status']);
        $this->assertTrue($c);
    }

    public function testActsLikeArray()
    {
        $deferred = new Deferred();
        $f = new FutureArray(
            $deferred->promise(),
            function () use (&$c, $deferred) {
                $deferred->resolve(['status' => 200]);
            }
        );

        $this->assertTrue(isset($f['status']));
        $this->assertEquals(200, $f['status']);
        $this->assertEquals(['status' => 200], $f->wait());
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
        $deferred = new Deferred();
        $f = new FutureArray($deferred->promise(), function () {});
        $f->foo;
    }

    public function testAfterThenActsLikeFutureArray()
    {
        $deferred = new Deferred();
        $f = new FutureArray(
            $deferred->promise(),
            function () use (&$c, $deferred) {
                $deferred->resolve(['status' => 200]);
            }
        );
        $f = $f->then(function(array $result) {
            $result['status']++;

            return $result;
        });

        $this->assertInstanceOf('GuzzleHttp\Ring\Future\FutureArray', $f);
        $this->assertFalse($this->readAttribute($f, 'isRealized'));
        $this->assertEquals(201, $f['status']);
    }
}
