<?php

namespace Pressware\AwesomeSupport\Notifications\Contracts;

interface ExceptionHandlerInterface
{
    /**
     * Register a listener.  Listeners are called when an error
     * is intercepted.
     *
     * @since 0.1.0
     *
     * @param string $key
     * @param callable|null $callback
     *
     * @return boolean
     */
    public function registerListener($key, $callback);

    /**
     * Setup and register our Error Handler.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function register();

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param int $errorLevel
     * @param string $message
     * @param string $file
     * @param int $line
     *
     * @return void
     */
    public function handleError($errorLevel, $message, $file = '', $line = 0);

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @param Throwable|Exception $error
     *
     * @return void
     * @throws
     */
    public function handleException($error);

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown();
}
