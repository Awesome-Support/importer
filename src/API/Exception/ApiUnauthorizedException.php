<?php

namespace Pressware\AwesomeSupport\API\Exception;

use GuzzleHttp\Exception\ClientException;

class ApiUnauthorizedException extends ApiException
{
    /**
     * ApiServerException constructor.
     *
     * @param ClientException $error Guzzle's ServerException is thrown for 500 level errors.
     * @param int $helpDeskName
     * @param string $moduleName
     * @param array $context
     * @link http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions
     */
    public function __construct(ClientException $error, $helpDeskName, $moduleName = '', array $context = [])
    {
        $message = $error->getMessage();
        $message .= sprintf(
            ' [details] %s reports the authorization credentials are invalid.',
            $helpDeskName
        );

        $this->ajaxMessage = sprintf(
            __(
                '%s could not authenticate your request. Check your credentials above. [Error Code: %s]',
                'awesome-support-importer'
            ),
            $helpDeskName,
            $error->getCode()
        );

        parent::__construct($message, $error, $helpDeskName, $moduleName, $context);
    }
}
