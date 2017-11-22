<?php

namespace Pressware\AwesomeSupport\API\Abstracts;

use Pressware\AwesomeSupport\API\Exception\ApiException;
use Pressware\AwesomeSupport\API\Exception\ApiResponseThrowableException;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleNotifier
{
    const STATUS_CODE_OK           = 200;
    const STATUS_CODE_UNAUTHORIZED = 401;

    /**
     * @var NotificationInterface
     */
    protected $notifier;

    /**
     * Name of the Help Desk Provider
     * @var
     */
    protected $apiName;

    /**
     * @var string
     */
    protected $moduleName;

    /**
     * Constructor.
     *
     * @param NotificationInterface $notifier Error and log handler
     */
    public function __construct(NotificationInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    /**
     * Initialize.
     *
     * @since 0.1.0
     *
     * @param string $apiName Name of the help desk API/provider
     * @param string $moduleName Name of the module.
     *
     * @return void
     */
    public function init($apiName, $moduleName)
    {
        $this->apiName    = $apiName;
        $this->moduleName = $moduleName;
    }

    /**
     * Throw the exception.
     *
     * @since 0.1.0
     *
     * @param \Exception $error
     * @param array $context
     *
     * @return void
     * @throws ApiException
     */
    public function handleException($error, array $context = [])
    {
        switch (get_class($error)) {
            // Connection errors.
            case 'GuzzleHttp\Exception\ConnectException':
                $exceptionClass = 'Pressware\AwesomeSupport\API\Exception\ApiConnectException';
                break;
            // 500 level errors.
            case 'GuzzleHttp\Exception\ServerException':
                $exceptionClass = 'Pressware\AwesomeSupport\API\Exception\ApiServerException';
                break;
            // 400 errors.
            case 'GuzzleHttp\Exception\ClientException':
                if ($error->hasResponse() &&
                    self::STATUS_CODE_UNAUTHORIZED === $error->getResponse()->getStatusCode()) {
                    $exceptionClass = 'Pressware\AwesomeSupport\API\Exception\ApiUnauthorizedException';
                    break;
                }
                $exceptionClass = 'Pressware\AwesomeSupport\API\Exception\ApiClientException';
                break;
            // All other errors.
            default:
                throw new ApiException($error->getMessage(), $error, $this->apiName, $this->moduleName, $context);
        }

        throw new $exceptionClass($error, $this->apiName, $this->moduleName, $context);
    }

    /****************************
     * Trigger Log
     ***************************/

    /**
     * Log the response.
     *
     * @since 0.1.0
     *
     * @param ResponseInterface $response
     * @param string $endpoint
     *
     * @return void
     */
    public function logHttpSuccess(ResponseInterface $response, $endpoint)
    {
        $this->notifier->log(
            "Success. Received packets from {$this->apiName}" .
            " with endpoint {$endpoint}.",
            ['httpCode' => $response->getStatusCode()]
        );
    }

    /**
     * Log that we hit the rate limit.
     *
     * @since 0.1.0
     *
     * @param int|string $delay
     * @param int|string $retryAfterSecs
     *
     * @return void
     */
    public function logHitRateLimit($delay, $retryAfterSecs)
    {
        $this->notifier->log(
            "{$this->apiName} hit the rate limit. Delaying for {$retryAfterSecs} seconds. " .
            'Then request will resend.',
            ['httpCode' => 429, 'delay' => $delay]
        );
    }

    /**
     * Log that we finished getting the tickets.
     *
     * @since 0.1.0
     *
     * @param array $config
     *
     * @return void
     */
    public function logStarting(array $config)
    {
        $this->notifier->log(
            "Request received to get tickets from {$this->apiName}.",
            [
                'config' => $config,
                'module' => $this->moduleName,
            ]
        );
    }

    /**
     * Log that we finished getting the tickets.
     *
     * @since 0.1.0
     *
     * @param array $tickets
     *
     * @return void
     */
    public function logFinished(array $tickets)
    {
        $numberTickets = count($tickets);
        $this->notifier->log(
            "Successfully received and assembled {$numberTickets} tickets from {$this->apiName}.",
            [
                'numberTickets' => $numberTickets,
                'module'        => $this->moduleName,
            ]
        );
    }
}
