<?php

namespace Pressware\AwesomeSupport\API\Exception;

use GuzzleHttp\Exception\ServerException;

class ApiServerException extends ApiException
{
    /**
     * ApiServerException constructor.
     *
     * @param ServerException $error Guzzle's ServerException is thrown for 500 level errors.
     * @param int $helpDeskName
     * @param string $moduleName
     * @param array $context
     * @link http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions
     */
    public function __construct(ServerException $error, $helpDeskName, $moduleName = '', array $context = [])
    {
        $message = $error->getMessage();
        $message .= sprintf(
            ' [details] %s may be experiencing internal issues or undergoing scheduled maintenance.',
            $helpDeskName
        );

        $this->ajaxMessage = sprintf(
            __(
                '%s reported a problem on their server. They may be experiencing ' .
                'internal issues or undergoing scheduled maintenance. Try again later. [Error Code: %s]',
                'awesome-support-importer'
            ),
            $helpDeskName,
            $error->getCode()
        );

        parent::__construct($message, $error, $helpDeskName, $moduleName, $context);
    }
}
