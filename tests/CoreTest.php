<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\Core;
use GuzzleHttp\Ring\Future;
use GuzzleHttp\Stream\Stream;

class CoreTest extends \PHPUnit_Framework_TestCase
{
    public function testAddsThensWhenNoneExist()
    {
        $th = function () {};
        $request = [];
        $request = Core::then($request, $th);
        $this->assertSame($th, $request['then']);
    }

    public function testCreatesAggregateThensWithFirstResponse()
    {
        $th1 = function (array $response) {
            $response['a'] = 1;
            return $response;
        };
        $th2 = function (array $response) {};
        $request = ['then' => $th1];
        $request = Core::then($request, $th2);
        $this->assertNotSame($th1, $request['then']);
        $this->assertNotSame($th2, $request['then']);
        $this->assertEquals(['a' => 1], call_user_func($request['then'], []));
    }

    public function testCreatesAggregateThensWithSecondResponse()
    {
        $th1 = function (array $response) {};
        $th2 = function (array $response) {
            $response['a'] = 1;
            return $response;
        };
        $request = ['then' => $th1];
        $request = Core::then($request, $th2);
        $this->assertNotSame($th1, $request['then']);
        $this->assertNotSame($th2, $request['then']);
        $this->assertEquals(['a' => 1], call_user_func($request['then'], []));
    }


    public function testDerefReturnsArray()
    {
        $res = ['status' => 200];
        $this->assertInternalType('array', Core::deref($res));
    }

    public function testDerefReturnsArrayWhenFuture()
    {
        $future = new Future(function () { return ['status' => 200]; });
        $this->assertInternalType('array', Core::deref($future));
    }

    public function testReturnsNullNoHeadersAreSet()
    {
        $this->assertNull(Core::header([], 'Foo'));
        $this->assertNull(Core::firstHeader([], 'Foo'));
    }

    public function testReturnsFirstHeaderWhenSimple()
    {
        $this->assertEquals('Bar', Core::firstHeader([
            'headers' => ['Foo' => ['Bar', 'Baz']]
        ], 'Foo'));
    }

    public function testReturnsFirstHeaderWhenMultiplePerLine()
    {
        $this->assertEquals('Bar', Core::firstHeader([
            'headers' => ['Foo' => ['Bar, Baz']]
        ], 'Foo'));
    }

    public function testExtractsCaseInsensitiveHeader()
    {
        $this->assertEquals(
            'hello',
            Core::header(['headers' => ['foo' => ['hello']]], 'FoO')
        );
    }

    public function testExtractsHeaderLines()
    {
        $this->assertEquals(
            ['bar', 'baz'],
            Core::headerLines([
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
            'headers' => ['host' => ['foo.com']],
            'uri'     => '/'
        ];

        $this->assertEquals('http://foo.com/', Core::url($req));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No Host header was provided
     */
    public function testEnsuresHostIsAvailableWhenCreatingUrls()
    {
        Core::url([]);
    }

    public function testCreatesUrlWithQueryString()
    {
        $req = [
            'scheme'       => 'http',
            'headers'      => ['host' => ['foo.com']],
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
            'Abc' => ['123'],
            'Def' => ['a, b']
        ], Core::headersFromLines($lines));
    }

    public function testParsesHeadersFromLinesWithMultipleLines()
    {
        $lines = ['Foo: bar', 'Foo: baz', 'Foo: 123'];
        $this->assertEquals([
            'Foo' => ['bar', 'baz', '123'],
        ], Core::headersFromLines($lines));
    }

    public function testCreatesArrayCallFunctions()
    {
        $called = [];
        $a = function ($a, $b) use (&$called) {
            $called['a'] = func_get_args();
        };
        $b = function ($a, $b) use (&$called) {
            $called['b'] = func_get_args();
        };
        $c = Core::callArray([$a, $b]);
        $c(1, 2);
        $this->assertEquals([1, 2], $called['a']);
        $this->assertEquals([1, 2], $called['b']);
    }

    public function testRewindsGuzzleStreams()
    {
        $str = Stream::factory('foo');
        $this->assertTrue(Core::rewindBody(['body' => $str]));
    }

    public function testRewindsStreams()
    {
        $str = Stream::factory('foo')->detach();
        $this->assertTrue(Core::rewindBody(['body' => $str]));
    }

    public function testRewindsIterators()
    {
        $iter = new \ArrayIterator(['foo']);
        $this->assertTrue(Core::rewindBody(['body' => $iter]));
    }

    public function testRewindsStrings()
    {
        $this->assertTrue(Core::rewindBody(['body' => 'hi']));
    }

    public function testRewindsToStrings()
    {
        $this->assertTrue(Core::rewindBody(['body' => new StrClass()]));
    }

    public function testDescribesType()
    {
        $this->assertEquals('string', Core::describeType('foo'));
        $this->assertEquals(
            'object (GuzzleHttp\Tests\Ring\StrClass)',
            Core::describeType(new StrClass())
        );
    }
}

final class StrClass
{
    public function __toString()
    {
        return 'foo';
    }
}
