<?php

namespace Pressware\AwesomeSupport\API\Exception;

use Pressware\AwesomeSupport\Notifications\Exceptions\ThrowableException;

class ApiException extends ThrowableException
{
    /**
     * @var array
     */
    protected $errorDetails = [];

    /**
     * ApiException constructor.
     * @param string $message
     * @param $error
     * @param string $helpDeskName
     * @param string $moduleName
     * @param array $context
     */
    public function __construct($message, $error, $helpDeskName, $moduleName, array $context = [])
    {
        if (method_exists($error, 'hasResponse') && $error->hasResponse()) {
            $message .= $this->getRequestMessage($error);
        }

        if (!$this->hasAjaxMessage()) {
            $this->ajaxMessage = sprintf(
                __('There was a problem getting the tickets from %s. [Error Code: %s]', 'awesome-support-importer'),
                $helpDeskName,
                $error->getCode()
            );
        }

        parent::__construct($message, $error->getCode(), $moduleName, $context, $helpDeskName, $error);
    }

    public function getErrorDetails()
    {
        return $this->errorDetails;
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
        $message = $this->toMessage($this->getMessage());
        $message .= ' Error Details: ' . implode($this->getErrorDetails());
        return $message;
    }

    protected function getRequestMessage($error)
    {
        $request = $error->getRequest();
        // Unsuccessful response, log what we can
        $message = ' [url] ' . $request->getUri();
        $message .= ' [http method] ' . $request->getMethod();
        $message .= ' [body] ' . $request->getBody()->getContents();
        return $message;
    }
}
