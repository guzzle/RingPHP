<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\ValidatedDeferredType;

class ValidatedDeferredTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected the resolved value to be of type "array", but got string(3) "foo"
     */
    public function testEnsuresIsArray()
    {
        $def = new ValidatedDeferredType('array');
        $def->resolve('foo');
    }

    public function testResolvesCorrectly()
    {
        $def = new ValidatedDeferredType('array');
        $def->resolve(['foo' => 'bar']);
    }
}
