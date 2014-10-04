<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\ValidatedDeferredInstance;

class ValidatedDeferredInstanceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected the resolved value to be an instance of "foo", but got string(3) "foo"
     */
    public function testEnsuresIsArray()
    {
        $def = new ValidatedDeferredInstance('foo');
        $def->resolve('foo');
    }

    public function testResolvesCorrectly()
    {
        $def = new ValidatedDeferredInstance('ArrayObject');
        $def->resolve(new \ArrayObject());
    }
}
