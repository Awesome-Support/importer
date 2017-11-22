<?php

namespace Pressware\AwesomeSupport\Notifications;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Pressware\AwesomeSupport\Notifications\Contracts\LoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
* @SuppressWarnings(PHPMD.TooManyPublicMethods)
*/
class Logger implements LoggerInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var PsrLoggerInterface
     */
    protected $monolog;

    /**
     * @var array
     */
    protected $levels;

    /**
     * Create a new logger instance.
     *
     * @param PsrLoggerInterface $monolog
     * @param array $config
     * @param string $type Type of logger.
     */
    public function __construct(PsrLoggerInterface $monolog, array $config, $type = 'error')
    {
        $this->monolog = $monolog;
        $this->config = $config;
        $this->levels = $config['levels'];
        $this->setup($type);
    }

    protected function setup($type)
    {
        $key = 'error' === $type
            ? 'errorLogPath'
            : 'logPath';

        $this->monolog->pushHandler(new StreamHandler(
            $this->config['rootPath'] . $this->config[$key],
            MonologLogger::DEBUG
        ));
    }

    /**
     * Log an alert message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function alert($message, array $context = [])
    {
        return $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a critical message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function critical($message, array $context = [])
    {
        return $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a debug message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function debug($message, array $context = [])
    {
        return $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an emergency message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function emergency($message, array $context = [])
    {
        return $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an error message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function error($message, array $context = [])
    {
        return $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an informational message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function info($message, array $context = [])
    {
        return $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a notice to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function notice($message, array $context = [])
    {
        return $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a warning message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function warning($message, array $context = [])
    {
        return $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a message to the logs.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function log($level, $message, array $context = [])
    {
        return $this->writeLog($level, $message, $context);
    }

    /**
     * Dynamically pass log calls into the writer.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    public function write($level, $message, array $context = [])
    {
        return $this->writeLog($level, $message, $context);
    }

    /**
     * Write a message to Monolog.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return boolean Whether the record has been processed
     */
    protected function writeLog($level, $message, $context)
    {
        return $this->monolog->{$level}($message, $context);
    }

    /**
     * Handle when a non-existent method is called.
     *
     * @since 0.1.0
     *
     * @param string $missingMethodName
     * @param mixed $args
     *
     * @return bool
     */
    public function __call($missingMethodName, $args)
    {
        return false;
    }
}
