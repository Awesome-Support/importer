<?php

namespace Pressware\AwesomeSupport\Notifications\Contracts;

interface NotificationInterface
{
    /**
     * Start listening for exceptions.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function startListeningForErrors();

    /**
     * Fire the error logger to record the exception.
     *
     * @since 0.1.0
     *
     * @param array $errorPacket
     * @param array $context
     *
     * @return void
     */
    public function fireErrorLogger(array $errorPacket, array $context = []);

    /**
     * Fire the informational logger to record a message.
     *
     * @since 0.1.0
     *
     * @param string $message Message to be recorded in the log
     * @param array $context (optional)
     *
     * @return void
     */
    public function log($message, $context = []);
}
