<?php

namespace Pressware\AwesomeSupport\Tests\Notifications;

use Mockery;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Exception\ApiNotAvailable;
use Pressware\AwesomeSupport\Notifications\ExceptionHandler;
use Monolog\Logger as MonologLogger;
use ErrorException;

class ExceptionHandlerTest extends TestCase
{
    protected $exceptionHandler;
    protected $mock;

    protected function setUp()
    {
        parent::setUp();

        $this->exceptionHandler = new ExceptionHandler([
            'levels' => [
                'emergency' => MonologLogger::EMERGENCY,
                'alert'     => MonologLogger::ALERT,
                'critical'  => MonologLogger::CRITICAL,
                'error'     => MonologLogger::ERROR,
                'warning'   => MonologLogger::WARNING,
                'notice'    => MonologLogger::NOTICE,
                'info'      => MonologLogger::INFO,
                'debug'     => MonologLogger::DEBUG,
            ],
        ]);
        $this->mock             = Mockery::mock($this->exceptionHandler);
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testHandleError()
    {
        $this->expectException(ErrorException::class);
        $this->exceptionHandler->handleError(E_ERROR, __METHOD__);
    }

    public function testWarning()
    {
        $this->expectException(\ErrorException::class);
        $this->exceptionHandler->handleError(E_WARNING, __METHOD__);
    }

    public function testListenerForWarning()
    {
        $errorMessage = 'testing warning callback';
        $this->exceptionHandler->registerListener(
            __CLASS__,
            function ($errorPacket, $context) use ($errorMessage) {
                $this->assertEquals('warning', $errorPacket['level']);
                $this->assertEquals(E_WARNING, $errorPacket['statusCode']);
                $this->assertEquals($errorMessage, $errorPacket['message']);
                $this->assertSame([], $context);
            }
        );

        $this->expectException(\ErrorException::class);
        $this->exceptionHandler->handleError(E_WARNING, $errorMessage, __FILE__, __LINE__);
    }

    public function testNotice()
    {
        $this->expectException(\ErrorException::class);
        $this->exceptionHandler->handleError(E_NOTICE, __METHOD__);
    }

    public function testListenerForNotice()
    {
        $errorMessage = 'testing notice callback';
        $this->exceptionHandler->registerListener(
            __CLASS__,
            function ($errorPacket, $context) use ($errorMessage) {
                $this->assertEquals('notice', $errorPacket['level']);
                $this->assertEquals(E_NOTICE, $errorPacket['statusCode']);
                $this->assertEquals($errorMessage, $errorPacket['message']);
                $this->assertSame([], $context);
            }
        );

        $this->expectException(\ErrorException::class);
        $this->exceptionHandler->handleError(E_NOTICE, $errorMessage, __FILE__, __LINE__);
    }

    public function testApiNotAvailableException()
    {
        $this->expectException(ApiNotAvailable::class);
        $this->exceptionHandler->handleException(new ApiNotAvailable('ExceptionHandlerTest'));
    }

    public function testListenerForApiNotAvailableException()
    {
        $this->exceptionHandler->registerListener(
            __CLASS__,
            function ($errorPacket, $context) {
                $this->assertEquals('error', $errorPacket['level']);
                $expected = 404;
                $this->assertEquals($expected, $errorPacket['statusCode']);
                $this->assertEquals(
                    'The requested Help Desk API [ExceptionHandlerTest] is not available with this plugin.' .
                    ' [Help Desk Provider: ExceptionHandlerTest] [Error: 404]',
                    $errorPacket['message']
                );
                $this->assertSame([], $context);
            }
        );

        $this->expectException(ApiNotAvailable::class);
        $this->exceptionHandler->handleException(new ApiNotAvailable('ExceptionHandlerTest'));
    }

    public function testListenerForNoSeverityCode()
    {
        $errorMessage = 'testing no severity code';
        $this->exceptionHandler->registerListener(
            __CLASS__,
            function ($errorPacket, $context) use ($errorMessage) {
                $this->assertEquals('error', $errorPacket['level']);
                $this->assertEquals(400, $errorPacket['statusCode']);
                $this->assertEquals($errorMessage, $errorPacket['message']);
                $this->assertSame([], $context);
            }
        );

        $this->expectException(\ErrorException::class);
        $this->exceptionHandler->handleError(-1, $errorMessage);
    }
}
