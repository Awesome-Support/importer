<?php

namespace Pressware\AwesomeSupport\API\Exception;

use GuzzleHttp\Exception\ClientException;

class ApiClientException extends ApiException
{
    const STATUS_CODE_NOT_FOUND = 404;
    /**
     * ApiServerException constructor.
     *
     * @param ClientException $error Guzzle's ServerException is thrown for 400 level errors.
     * @param int $helpDeskName
     * @param string $moduleName
     * @param array $context
     * @link http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions
     */
    public function __construct(ClientException $error, $helpDeskName, $moduleName = '', array $context = [])
    {
        $this->ajaxMessage = sprintf(
            $this->getAjaxMessagePattern($error, $helpDeskName),
            $helpDeskName,
            $error->getCode()
        );

        parent::__construct($error->getMessage(), $error, $helpDeskName, $moduleName, $context);
    }

    protected function getAjaxMessagePattern($error, $helpDeskName)
    {
        if ('Zendesk' === $helpDeskName && $error->getCode() === self::STATUS_CODE_NOT_FOUND) {
            return __(
                'There was a problem connecting to %s. Verify the subdomain you entered above. ' .
                'Then try again. [Error Code: %s]',
                'awesome-support-importer'
            );
        }

        return __(
            'There was a problem connecting to %s. Try again later. [Error Code: %s]',
            'awesome-support-importer'
        );
    }
}
