<?php

namespace Pressware\AwesomeSupport\Tests\Notifications;

use Mockery;
use PHPUnit\Framework\TestCase;
use Monolog\Logger as MonologLogger;
use Pressware\AwesomeSupport\Notifications\Notifier;

class NotifierTest extends TestCase
{
    protected $config;
    protected $errorLogger;
    protected $infoLogger;
    protected $exceptionHandler;

    protected function setUp()
    {
        parent::setUp();

        $this->config = [
            'rootPath'     => '',
            'logPath'      => '/logs/info.log',
            'errorLogPath' => '/logs/error.log',
            'levels'       => [
                'emergency' => MonologLogger::EMERGENCY,
                'alert'     => MonologLogger::ALERT,
                'critical'  => MonologLogger::CRITICAL,
                'error'     => MonologLogger::ERROR,
                'warning'   => MonologLogger::WARNING,
                'notice'    => MonologLogger::NOTICE,
                'info'      => MonologLogger::INFO,
                'debug'     => MonologLogger::DEBUG,
            ],
        ];

        $this->errorLogger      = Mockery::mock(
            'Pressware\AwesomeSupport\Notifications\Logger, '
            . 'Pressware\AwesomeSupport\Notifications\Contracts\LoggerInterface',
            [
                Mockery::mock('Monolog\Logger')->shouldIgnoreMissing(), $this->config,
            ]
        )->makePartial();
        $this->infoLogger       = Mockery::mock(
            'Pressware\AwesomeSupport\Notifications\Contracts\LoggerInterface'
        );
        $this->exceptionHandler = Mockery::mock(
            'Pressware\AwesomeSupport\Notifications\Contracts\ExceptionHandlerInterface'
        );
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testInit()
    {
        $notifier = $this->createNotifier();
        $this->assertInstanceOf('Pressware\AwesomeSupport\Notifications\Notifier', $notifier);
    }

    public function testLog()
    {
        $message = 'Testing an informational message';
        $context = ['user' => 'Tonya', 'foo' => 'baz'];

        $notifier = $this->createNotifier();

        $this->infoLogger->shouldReceive('log')
            ->once()
            ->with('info', $message, $context)
            ->andReturn(true);

        $result = $notifier->log($message, $context);
        $this->assertTrue($result);
    }

    public function testFireErrorLoggerInvalidLevel()
    {
        $errorPacket = [
            'level'      => 'levelDoesntExist',
            'statusCode' => 400,
            'message'    => 'Testing fire error logger',
        ];
        $context     = ['foo' => 'foobar', 'baz' => 'bazbar'];

        $notifier = $this->createNotifier();

        $this->assertFalse(method_exists($this->errorLogger, 'levelDoesntExist'));

        $result = $notifier->fireErrorLogger($errorPacket, $context);
        $this->assertFalse($result);
    }

    public function testFireErrorLogger()
    {
        $errorPacket = [
            'level'      => 'error',
            'statusCode' => 400,
            'message'    => 'Testing fire error logger',
        ];
        $context     = ['foo' => 'foobar', 'baz' => 'bazbar'];

        $notifier = $this->createNotifier();

        $this->errorLogger->shouldReceive('error')
            ->once()
            ->with($errorPacket['message'], $context)
            ->andReturn(true);

        $this->assertTrue(is_callable([$this->errorLogger, 'error']));
        $result = $notifier->fireErrorLogger($errorPacket, $context);
        $this->assertTrue($result);
    }

    public function testStartListeningForErrors()
    {
        $notifier = $this->createNotifier();

        $this->exceptionHandler->shouldReceive('register');

        $this->exceptionHandler->shouldReceive('registerListener')
            ->once()
            ->with('notifier', [$notifier, 'fireErrorLogger'])
            ->andReturn(true);

        $this->assertTrue($notifier->startListeningForErrors());
    }

    protected function createNotifier()
    {
        return new Notifier($this->config, $this->errorLogger, $this->infoLogger, $this->exceptionHandler);
    }
}
