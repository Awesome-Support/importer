<?php

namespace Pressware\AwesomeSupport\Notifications;

use Exception;
use ErrorException;
use Pressware\AwesomeSupport\Notifications\Contracts\ExceptionHandlerInterface;
use Pressware\AwesomeSupport\Notifications\Exceptions\ExceptionThrowableInterface;
use Pressware\AwesomeSupport\Notifications\Exceptions\ThrowableException;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class ExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $levels;

    /**
     * @var array
     */
    protected $listeners;

    /**
     * ErrorHandler constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config    = $config;
        $this->levels    = $config['levels'];
        $this->listeners = [];
    }

    /**
     * Register a listener.  Listeners are called when an error is intercepted.
     *
     * @since 0.1.0
     *
     * @param string $key
     * @param callable|null $callback
     *
     * @return bool|void
     */
    public function registerListener($key, $callback)
    {
        if (is_callable($callback)) {
            $this->listeners[$key] = $callback;
            return true;
        }
    }

    /**
     * Setup and register our Error Handler.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function register()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);

        // Nope, not in debug mode.
        if (!defined('SCRIPT_DEBUG') || !SCRIPT_DEBUG) {
            // Turn off displaying the errors.
            ini_set('display_errors', false);
            // Report all errors except E_NOTICE
            error_reporting(E_ALL & ~E_NOTICE);
        }
    }

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param int $errorLevel
     * @param string $message
     * @param string $file
     * @param int $line
     *
     * @return void
     * @throws ErrorException
     */
    public function handleError($errorLevel, $message, $file = '', $line = 0)
    {
        if (error_reporting() && $errorLevel) {
            throw new \ErrorException($message, 0, $errorLevel, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @since 0.1.1
     *
     * @param Throwable|Exception $error
     *
     * @return void
     * @throws \ErrorException
     */
    public function handleException($error)
    {
        $this->fireListeners($error);

        if (!$error instanceof Exception) {
            $error = new \ErrorException(
                $error->getMessage(),
                $error->getCode(),
                E_ERROR,
                $error->getFile(),
                $error->getLine()
            );
        }

        $this->fireThrow($error);
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @since 0.1.1
     *
     * @return void
     */
    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error));
        }
    }

    /***************************
     * Protected Workers
     **************************/

    /**
     * Handles throwing the error to the PHP displayer or, if AJAX is
     * happening, then passes it to WordPress to process the error.
     *
     * @since 0.1.0
     *
     * @param Throwable|Exception $error The error to throw.
     * @param int $minThreshold Minimum error code threshold. Default 400.
     * @return void
     * @throws
     */
    protected function fireThrow($error, $minThreshold = 400)
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $packet = $this->getAjaxPacket($error);

            wp_send_json_error(
                $packet,
                $packet['code'] < $minThreshold ? $minThreshold : $packet['code']
            );
        }

        if (error_reporting()) {
            throw $error;
        }
    }

    /**
     * Get the AJAX Packet.
     *
     * @since 0.1.0
     *
     * @param ExceptionThrowableInterface|\Throwable|Exception $error
     *
     * @return array
     */
    protected function getAjaxPacket($error)
    {
        if ($this->isThrowable($error)) {
            return $error->getAjax();
        }

        return [
            'code'    => $error->getCode(),
            'message' => $error->getMessage(),
        ];
    }

    /**
     * Get the several level from the Http Code.
     *
     * @since 1.0.0
     *
     * @param Thowable|Exception $error
     * @param string|int $httpCode
     *
     * @return array
     */
    protected function getHttpSeverityLevel($error, $httpCode)
    {
        $httpCode = $this->getForBuiltins($error, $httpCode);

        foreach ($this->levels as $name => $levelCode) {
            if ($httpCode >= $levelCode) {
                return [$name, $httpCode];
            }
        }
        $threshold = 400;
        return ['', $httpCode < $threshold ? $threshold : $httpCode];
    }

    /**
     * Get the code for the built-in error severity levels.
     *
     * @since 0.1.0
     *
     * @param $error
     * @param $httpCode
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function getForBuiltins($error, $httpCode)
    {
        if (!$httpCode) {
            return $this->levels['error'];
        }

        if (!method_exists($error, 'getSeverity')) {
            return $httpCode;
        }

        switch ($error->getSeverity()) {
            case E_WARNING:
                return $this->levels['warning'];
            case E_NOTICE:
                return $this->levels['notice'];
            case E_ERROR:
                return $this->levels['error'];
        }

        list($levelName, $code) = $this->getHttpSeverityLevel($error, $httpCode);
        return $code;
    }

    /**
     * Create a new fatal exception instance from an error array.
     *
     * @since 0.1.1
     *
     * @param  array $error
     * @return \ErrorException
     */
    protected function fatalExceptionFromError(array $error)
    {
        return new \ErrorException(
            $error['message'],
            $error['type'],
            0,
            $error['file'],
            $error['line']
        );
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int $type
     *
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array(
            $type,
            [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]
        );
    }

    /**
     * Prepare the error packet and then fire all of the registered listeners
     * to alert them an exception has been thrown.
     *
     * @since 0.1.0
     *
     * @param Throwable|Exception $error Active error
     * @param null|int $errorCode
     *
     * @return void
     */
    protected function fireListeners($error, $errorCode = null)
    {
        list($errorPacket, $context) = $this->packageError($error, $errorCode);

        foreach ($this->listeners as $callback) {
            call_user_func($callback, $errorPacket, $context);
        }
    }

    public function packageError($error, $errorCode = null)
    {
        $errorCode = is_null($errorCode) ? $error->getCode() : $errorCode;
        if (!$errorCode) {
            $errorCode = $this->levels['error'];
        }

        $errorPacket = [
            'level'       => '',
            'statusCode'  => '',
            'message'     => '',
            'file'        => $error->getfile(),
            'line'        => $error->getLine(),
            'trace'       => $error->getTraceAsString(),
            'userMessage' => $this->isThrowable($error) ? $error->getAjax() : '',
        ];

        list($errorPacket['level'], $errorPacket['statusCode']) = $this->getHttpSeverityLevel($error, $errorCode);
        $errorPacket['message'] = $this->isThrowable($error)
            ? $error->getLogMessage()
            : $error->getMessage();

        return [$errorPacket, $this->isThrowable($error) ? $error->getContext() : []];
    }

    protected function isThrowable($error)
    {
        return ($error instanceof ExceptionThrowableInterface);
    }
}
