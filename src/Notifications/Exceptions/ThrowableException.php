<?php

namespace Pressware\AwesomeSupport\Notifications\Exceptions;

use Exception;

class ThrowableException extends Exception implements ExceptionThrowableInterface
{
    use ExceptionTrait;

    public function __construct(
        $message,
        $errorCode,
        $moduleName,
        array $context = [],
        $helpDesk = '',
        $previous = null
    ) {
        $this->helpDesk   = $helpDesk;
        $this->moduleName = $moduleName;
        $this->context    = $context;
        parent::__construct($message, $errorCode, $previous);
    }

    /**
     * Get the log message.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getLogMessage()
    {
        return $this->toMessage($this->getMessage(), false);
    }
}
