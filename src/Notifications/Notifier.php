<?php

namespace Pressware\AwesomeSupport\Notifications;

use Pressware\AwesomeSupport\Notifications\Contracts\ExceptionHandlerInterface;
use Pressware\AwesomeSupport\Notifications\Contracts\LoggerInterface;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

class Notifier implements NotificationInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Informational Logger
     * @var Logger
     */
    protected $infoLogger;

    /**
     * Exception Logger
     * @var Logger
     */
    protected $errorLogger;

    /**
     * @var ExceptionHandlerInterface
     */
    protected $exceptionHandler;

    /**
     * Notifier constructor.
     *
     * @param array $config
     * @param LoggerInterface $errorLogger
     * @param LoggerInterface $infoLogger
     * @param ExceptionHandlerInterface $exceptionHandler
     */
    public function __construct(
        array $config,
        LoggerInterface $errorLogger,
        LoggerInterface $infoLogger,
        ExceptionHandlerInterface $exceptionHandler
    ) {
        $this->config           = $config;
        $this->errorLogger      = $errorLogger;
        $this->infoLogger       = $infoLogger;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Start listening for exceptions.
     *
     * @since 0.1.0
     *
     * @return boolean
     */
    public function startListeningForErrors()
    {
        $this->exceptionHandler->register();
        return $this->exceptionHandler->registerListener('notifier', [$this, 'fireErrorLogger']);
    }

    /**
     * Log the error.
     *
     * @since 0.1.0
     *
     * @param $error
     *
     * @return array
     */
    public function logError($error)
    {
        list($errorPacket, $context) = $this->exceptionHandler->packageError($error, $error->getCode());
        $this->fireErrorLogger($errorPacket, $context);
        return [$errorPacket, $context];
    }

    /** Fire the error logger to record the exception.
     *
     * @since 0.1.0
     *
     * @param array $errorPacket
     * @param array $context
     *
     * @return boolean True when error is logged.
     */
    public function fireErrorLogger(array $errorPacket, array $context = [])
    {
        if (!method_exists($this->errorLogger, $errorPacket['level'])) {
            return false;
        }

        $methodName = $errorPacket['level'];
        return $this->errorLogger->$methodName($errorPacket['message'], $context);
    }

    /**
     * Fire the informational logger to record a message.
     *
     * @since 0.1.0
     *
     * @param string $message Message to be recorded in the log
     * @param array $context (optional)
     *
     * @return boolean True when message is logged.
     */
    public function log($message, $context = [])
    {
        return $this->infoLogger->log('info', $message, $context);
    }
}
