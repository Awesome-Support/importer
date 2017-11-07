<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\AExceptions;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Exception\ApiResponseThrowableException;
use Pressware\AwesomeSupport\API\Exception\ApiUnauthorizedException;

class ApiUnauthorizedExceptionTest extends TestCase
{
    protected $error;

    protected function setUp()
    {
        parent::setUp();
        $this->error = new ApiUnauthorizedException(
            ClientException::create(
                new Request('GET', '/'),
                new Response(401, [], 'Whoops, you are not authorized.')
            ),
            'Foo'
        );
    }

    public function testException()
    {
        $this->assertInstanceOf('Pressware\AwesomeSupport\API\Exception\ApiUnauthorizedException', $this->error);

        $this->assertContains('Client error: `GET /`', $this->error->getMessage());
        $this->assertContains('401 Unauthorized', $this->error->getMessage());
        $this->assertContains('[url] / [http method] GET [body] ', $this->error->getMessage());
        $this->assertContains('Whoops, you are not authorized.', $this->error->getMessage());
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
        $error = new ApiUnauthorizedException(
            ClientException::create(
                new Request('GET', '/'),
                new Response(401, [], 'Whoops, you are not authorized.')
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

    public function testErrorCode()
    {
        $expected = 401;
        $this->assertEquals($expected, $this->error->getCode());
    }

    public function testContext()
    {
        $error = new ApiUnauthorizedException(
            ClientException::create(
                new Request('GET', '/'),
                new Response(401, [], 'Whoops, you are not authorized.')
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
            'Foo could not authenticate your request. Check your credentials above. [Error Code: 401]',
            $this->error->getAjaxMessage()
        );
    }

    public function testNoAjaxMessage()
    {
        $this->error->setAjaxMessage('');
        $this->assertFalse($this->error->hasAjaxMessage());
        $this->assertSame(
            $this->error->getMessage() . ' [Help Desk Provider: Foo] [Error: 401]',
            $this->error->getAjaxMessage()
        );
    }

    public function testGetAjaxPacket()
    {
        $this->assertTrue($this->error->hasAjaxMessage());
        $this->assertSame(
            [
                'code'    => 401,
                'message' => '<p>Foo could not authenticate your request. Check your credentials above. '.
                    '[Error Code: 401]</p>',
            ],
            $this->error->getAjax()
        );
    }

    public function testGetAjaxWithMessage()
    {
        $this->error->setAjaxMessage('');

        $this->assertSame(
            [
                'code'    => 401,
                'message' => '<p>' . $this->error->getMessage() . '</p><p>[Help Desk Provider: Foo] [Error: 401]</p>',
            ],
            $this->error->getAjax()
        );
    }
}
