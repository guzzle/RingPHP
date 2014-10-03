<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\ValidatedDeferred;

class ValidatedDeferredTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected the resolved value to be an array
     */
    public function testEnsuresIsArray()
    {
        $def = ValidatedDeferred::forArray();
        $def->resolve('foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected the resolved value to be an instance of Foo
     */
    public function testEnsuresIsInstance()
    {
        $def = ValidatedDeferred::forInstance('Foo');
        $def->resolve('foo');
    }
}
