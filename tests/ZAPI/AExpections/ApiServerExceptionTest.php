<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\AExceptions;

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Exception\ApiServerException;
use Pressware\AwesomeSupport\API\Exception\ApiResponseThrowableException;

class ApiServerExceptionTest extends TestCase
{
    protected $error;

    protected function setUp()
    {
        parent::setUp();

        $this->error = new ApiServerException(
            new ServerException(
                '500 Internal Server Error',
                new Request('GET', '/'),
                new Response(500)
            ),
            'Foo'
        );
    }

    public function testException()
    {
        $this->assertInstanceOf('Pressware\AwesomeSupport\API\Exception\ApiServerException', $this->error);

        $this->assertContains(
            '[details] Foo may be experiencing internal issues or undergoing scheduled maintenance.',
            $this->error->getMessage()
        );
        $this->assertContains('500 Internal Server Error', $this->error->getMessage());
        $this->assertContains('[url] / [http method] GET [body] ', $this->error->getMessage());
    }

    public function testErrorCode()
    {
        $expected = 500;
        $this->assertEquals($expected, $this->error->getCode());
    }

    public function testHelpDeskName()
    {
        $this->assertTrue($this->error->hasHelpDesk());
        $this->assertSame('Foo', $this->error->getHelpDesk());

        $this->error->setHelpDesk('Baz');
        $this->assertTrue($this->error->hasHelpDesk());
        $this->assertSame('Baz', $this->error->getHelpDesk());
    }

    public function testModuleName()
    {
        $error = new ApiServerException(
            new ServerException(
                '500 Internal Server Error',
                new Request('GET', '/'),
                new Response(400)
            ),
            'Foo',
            'Bar'
        );

        $this->assertTrue($error->hasModuleName());
        $this->assertSame('Bar', $error->getModule());

        $error->setModule('Baz');
        $this->assertTrue($error->hasModuleName());
        $this->assertSame('Baz', $error->getModule());
    }

    public function testContext()
    {
        $error = new ApiServerException(
            new ServerException(
                '500 Internal Server Error',
                new Request('GET', '/'),
                new Response(400)
            ),
            'Foo',
            'Bar',
            ['foobar' => 'baz']
        );

        $this->assertTrue($error->hasContext());
        $this->assertSame(['foobar' => 'baz'], $error->getContext());

        $error->setContext(['baz' => 'bar']);
        $this->assertTrue($error->hasContext());
        $this->assertSame(['baz' => 'bar'], $error->getContext());
    }

    public function testAjaxMessage()
    {
        $this->assertTrue($this->error->hasAjaxMessage());
        $this->assertSame(
            'Foo reported a problem on their server. They may be experiencing ' .
            'internal issues or undergoing scheduled maintenance. Try again later. [Error Code: 500]',
            $this->error->getAjaxMessage()
        );
    }

    public function testNoAjaxMessage()
    {
        $this->error->setAjaxMessage('');
        $this->assertFalse($this->error->hasAjaxMessage());
        $this->assertSame(
            $this->error->getMessage() . ' [Help Desk Provider: Foo] [Error: 500]',
            $this->error->getAjaxMessage()
        );
    }

    public function testGetAjaxPacket()
    {
        $this->assertTrue($this->error->hasAjaxMessage());
        $this->assertSame(
            [
                'code'    => 500,
                'message' => '<p>Foo reported a problem on their server. They may be experiencing ' .
                    'internal issues or undergoing scheduled maintenance. Try again later. [Error Code: 500]</p>',
            ],
            $this->error->getAjax()
        );
    }

    public function testGetAjaxWithMessage()
    {
        $this->error->setAjaxMessage('');

        $this->assertSame(
            [
                'code'    => 500,
                'message' => '<p>' . $this->error->getMessage() . '</p><p>[Help Desk Provider: Foo] [Error: 500]</p>',
            ],
            $this->error->getAjax()
        );
    }
}
