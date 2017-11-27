<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\AExceptions;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Exception\ApiClientException;
use Pressware\AwesomeSupport\API\Exception\ApiResponseThrowableException;

class ApiClientExceptionTest extends TestCase
{
    protected $error;

    protected function setUp()
    {
        parent::setUp();
        $this->error = new ApiClientException(
            ClientException::create(
                new Request('GET', '/'),
                new Response(400, [], 'Whoops, an error has occurred.')
            ),
            'Foo'
        );
    }

    public function testException()
    {
        $this->assertInstanceOf('Pressware\AwesomeSupport\API\Exception\ApiClientException', $this->error);

        $this->assertContains('Client error: `GET /`', $this->error->getMessage());
        $this->assertContains('400 Bad Request', $this->error->getMessage());
        $this->assertContains('[url] / [http method] GET [body] ', $this->error->getMessage());
    }

    public function testErrorCode()
    {
        $expected = 400;
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
        $error = new ApiClientException(
            ClientException::create(
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
        $error = new ApiClientException(
            ClientException::create(
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
            'There was a problem connecting to Foo. Try again later. [Error Code: 400]',
            $this->error->getAjaxMessage()
        );
    }

    public function testNoAjaxMessage()
    {
        $this->error->setAjaxMessage('');
        $this->assertFalse($this->error->hasAjaxMessage());
        $this->assertSame(
            $this->error->getMessage() . ' [Help Desk Provider: Foo] [Error: 400]',
            $this->error->getAjaxMessage()
        );
    }

    public function testGetAjaxPacket()
    {
        $this->assertTrue($this->error->hasAjaxMessage());
        $this->assertSame(
            [
                'code'    => 400,
                'message' => '<p>There was a problem connecting to Foo. Try again later. [Error Code: 400]</p>',
            ],
            $this->error->getAjax()
        );
    }

    public function testGetAjaxWithMessage()
    {
        $this->error->setAjaxMessage('');

        $this->assertSame(
            [
                'code'    => 400,
                'message' => '<p>' . $this->error->getMessage() . '</p><p>[Help Desk Provider: Foo] [Error: 400]</p>',
            ],
            $this->error->getAjax()
        );
    }
}
