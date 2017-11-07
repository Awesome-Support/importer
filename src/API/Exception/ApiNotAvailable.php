<?php

namespace Pressware\AwesomeSupport\API\Exception;

use Pressware\AwesomeSupport\Notifications\Exceptions\ThrowableException;

class ApiNotAvailable extends ThrowableException
{
    public function __construct($requestedApi = '', $moduleName = '', array $context = [], $previous = null)
    {
        $message = sprintf(
            'The requested Help Desk API [%s] is not available with this plugin.',
            $requestedApi
        );

        $this->ajaxMessage = $message;

        parent::__construct($message, 404, $moduleName, $context, $requestedApi, $previous);
    }
}
