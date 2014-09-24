<?php
namespace GuzzleHttp\Tests\Ring;

use GuzzleHttp\Ring\FutureValue;

class FutureValueTest extends \PHPUnit_Framework_TestCase
{
    public function testDerefReturnsValue()
    {
        $f = new FutureValue(function () {
            return 'foo';
        });
        $this->assertEquals('foo', $f->deref());
    }
}
