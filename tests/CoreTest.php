<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\Future;
use GuzzleHttp\Stream\Stream;

class CoreTest extends \PHPUnit_Framework_TestCase
{
    public function testThenReturnsFuture()
    {
        $future = new Future(function () {
            return ['status' => 200];
        });

        $called = false;
        $newFuture = Core::then($future, function ($response) use (&$called) {
            $this->assertSame(['status' => 200], $response);
            $called = true;
        });

        $this->assertInstanceOf('GuzzleHttp\Ring\Future', $newFuture);
        Core::deref($newFuture);
        $this->assertTrue($called);
    }

    public function testThenCallsImmediatelyWhenNotFuture()
    {
        $res = ['status' => 200];

        $called = false;
        $newArray = Core::then($res, function ($response) use ($res, &$called) {
            $this->assertSame($res, $response);
            $called = true;
        });

        $this->assertInternalType('array', $newArray);
        $this->assertTrue($called);
    }

    public function testFutureReturnsFutureResponse()
    {
        $future = Core::future(function () { return ['status' => 200]; });
        $this->assertInstanceOf('GuzzleHttp\Ring\Future', $future);
        $this->assertEquals(200, $future['status']);
    }

    public function testDerefReturnsArray()
    {
        $res = ['status' => 200];
        $this->assertInternalType('array', Core::deref($res));
    }

    public function testDerefReturnsArrayWhenFuture()
    {
        $future = Core::future(function () { return ['status' => 200]; });
        $this->assertInternalType('array', Core::deref($future));
    }

    public function testReturnsEmptyArrayWhenNoHeadersAreSet()
    {
        $this->assertNull(Core::header([], 'Foo'));
    }

    public function testExtractsCaseInsensitiveHeader()
    {
        $this->assertEquals(
            'hello',
            Core::header(['headers' => ['foo' => 'hello']], 'FoO')
        );
    }

    public function testExtractsHeader()
    {
        $this->assertEquals(
            ['bar', 'baz'],
            Core::header([
                'headers' => [
                    'Foo' => ['bar', 'baz']
                ]
            ], 'Foo')
        );
    }

    public function testExtractsHeaderAsString()
    {
        $this->assertEquals(
            'bar, baz',
            Core::header([
                'headers' => [
                    'Foo' => ['bar', 'baz']
                ]
            ], 'Foo', true)
        );
    }

    public function testReturnsNullWhenHeaderNotFound()
    {
        $this->assertNull(Core::header(['headers' => []], 'Foo'));
    }

    public function testCreatesUrl()
    {
        $req = [
            'scheme'  => 'http',
            'headers' => ['host' => 'foo.com'],
            'uri'     => '/'
        ];

        $this->assertEquals('http://foo.com/', Core::url($req));
    }

    public function testCreatesUrlWithQueryString()
    {
        $req = [
            'scheme'       => 'http',
            'headers'      => ['host' => 'foo.com'],
            'uri'          => '/',
            'query_string' => 'foo=baz'
        ];

        $this->assertEquals('http://foo.com/?foo=baz', Core::url($req));
    }

    public function testUsesUrlIfSet()
    {
        $req = ['url' => 'http://foo.com'];
        $this->assertEquals('http://foo.com', Core::url($req));
    }

    public function testReturnsNullWhenNoBody()
    {
        $this->assertNull(Core::body([]));
    }

    public function testReturnsStreamAsString()
    {
        $this->assertEquals(
            'foo',
            Core::body(['body' => Stream::factory('foo')])
        );
    }

    public function testReturnsString()
    {
        $this->assertEquals('foo', Core::body(['body' => 'foo']));
    }

    public function testReturnsResourceContent()
    {
        $r = fopen('php://memory', 'w+');
        fwrite($r, 'foo');
        rewind($r);
        $this->assertEquals('foo', Core::body(['body' => $r]));
        fclose($r);
    }

    public function testReturnsIteratorContent()
    {
        $a = new \ArrayIterator(['a', 'b', 'cd', '']);
        $this->assertEquals('abcd', Core::body(['body' => $a]));
    }

    public function testReturnsObjectToString()
    {
        $this->assertEquals('foo', Core::body(['body' => new StrClass]));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEnsuresBodyIsValid()
    {
        Core::body(['body' => false]);
    }

    public function testParsesHeadersFromLines()
    {
        $lines = ['Foo: bar', 'Foo: baz', 'Abc: 123', 'Def: a, b'];
        $this->assertEquals([
            'Foo' => ['bar', 'baz'],
            'Abc' => '123',
            'Def' => 'a, b'
        ], Core::headersFromLines($lines));
    }

    public function testParsesHeadersFromLinesWithMultipleLines()
    {
        $lines = ['Foo: bar', 'Foo: baz', 'Foo: 123'];
        $this->assertEquals([
            'Foo' => ['bar', 'baz', '123'],
        ], Core::headersFromLines($lines));
    }
}

final class StrClass
{
    public function __toString()
    {
        return 'foo';
    }
}
