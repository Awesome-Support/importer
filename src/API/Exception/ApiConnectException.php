<?php

namespace Pressware\AwesomeSupport\API\Exception;

use GuzzleHttp\Exception\ConnectException;

class ApiConnectException extends ApiException
{
    /**
     * ApiConnectException constructor.
     *
     * @param ConnectException $error Error is thrown when Guzzle cannot connect to the Help Desk.
     * @param int $helpDeskName
     * @param string $moduleName
     * @param array $context
     * @link http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions
     */
    public function __construct(ConnectException $error, $helpDeskName, $moduleName, array $context = [])
    {
        $message           = "Failed to connect to {$helpDeskName}. Details: {$error->getMessage()}";
        $this->ajaxMessage = sprintf(
            __('There was a problem connecting to %s. Try again later. [Error Code: %s]', 'awesome-support-importer'),
            $helpDeskName,
            $error->getCode()
        );

        parent::__construct($message, $error, $helpDeskName, $moduleName, $context);
    }
}
