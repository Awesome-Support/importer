<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\AExceptions;

use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Exception\ApiNotAvailable;

class ApiNotAvailableTest extends TestCase
{
    public function testException()
    {
        $error = new ApiNotAvailable('Foo');

        $this->assertInstanceOf(
            'Pressware\AwesomeSupport\API\Exception\ApiNotAvailable',
            $error
        );
        $this->assertSame(
            'The requested Help Desk API [Foo] is not available with this plugin.',
            $error->getMessage()
        );
    }

    public function testErrorCode()
    {
        $error = new ApiNotAvailable('Foo');

        $expected = 404;
        $this->assertEquals($expected, $error->getCode());
    }

    public function testAjaxMessage()
    {
        $error = new ApiNotAvailable('Foo');

        $this->assertTrue($error->hasAjaxMessage());
        $this->assertSame(
            'The requested Help Desk API [Foo] is not available with this plugin.',
            $error->getAjaxMessage()
        );
    }
}
