<?php

namespace Pressware\AwesomeSupport\Tests\Notifications;

use Mockery;
use PHPUnit\Framework\TestCase;
use Monolog\Logger as MonologLogger;
use Pressware\AwesomeSupport\Notifications\Logger;

class LoggerTest extends TestCase
{
    protected $config;

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
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testMonologSetup()
    {
        $monolog = Mockery::mock('Monolog\Logger');
        $monolog->shouldReceive('pushHandler')
            ->once();
        $logger = new Logger($monolog, $this->config, 'error');
        $this->assertInstanceOf('Pressware\AwesomeSupport\Notifications\Logger', $logger);
    }

    public function testInvokingError()
    {
        $monolog = Mockery::mock('Monolog\Logger');
        $monolog->shouldReceive('pushHandler')
            ->once();

        $logger = new Logger($monolog, $this->config, 'error');
        $monolog->shouldReceive('error')
            ->once()
            ->with('foo', [])
            ->andReturn(true);

        $result = $logger->error('foo');
        $this->assertTrue($result);
    }

    public function testInvokingInfo()
    {
        $monolog = Mockery::mock('Monolog\Logger');
        $monolog->shouldReceive('pushHandler')
            ->once();

        $logger = new Logger($monolog, $this->config, 'error');
        $monolog->shouldReceive('info')
            ->once()
            ->with('some message', ['foo' => 'bar'])
            ->andReturn(true);

        $result = $logger->info('some message', ['foo' => 'bar']);
        $this->assertTrue($result);
    }

    public function testLoggingInformationalMessage()
    {
        $message = 'Testing an informational message';
        $context = ['user' => 'Tonya', 'foo' => 'baz'];

        $monolog = Mockery::mock('Monolog\Logger');
        $monolog
            ->shouldReceive('pushHandler')
            ->once();

        $logger = new Logger($monolog, $this->config, 'info');

        $monolog->shouldReceive('info')
            ->once()
            ->with($message, $context)
            ->andReturn(true);

        $result = $logger->log('info', $message, $context);
        $this->assertTrue($result);
    }

    public function testWritingInformationalMessage()
    {
        $message = 'Testing an informational debug message';
        $context = ['user' => 'Tonya', 'foo' => 'baz'];

        $monolog = Mockery::mock('Monolog\Logger');
        $monolog
            ->shouldReceive('pushHandler')
            ->once();

        $logger = new Logger($monolog, $this->config, 'info');

        $monolog->shouldReceive('debug')
            ->once()
            ->with($message, $context)
            ->andReturn(true);

        $result = $logger->write('debug', $message, $context);
        $this->assertTrue($result);
    }
}
