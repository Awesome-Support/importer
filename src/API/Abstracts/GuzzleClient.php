<?php

namespace Pressware\AwesomeSupport\API\Abstracts;

use GuzzleHttp\Client;
use Pressware\AwesomeSupport\API\Exception\ApiException;
use Pressware\AwesomeSupport\API\Exception\ApiServerException;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\ClientException;
use Pressware\AwesomeSupport\API\Contracts\ApiInterface;
use Pressware\AwesomeSupport\API\Exception\ApiResponseThrowableException;
use Pressware\AwesomeSupport\Traits\CastToTrait;
use Psr\Http\Message\ResponseInterface;

abstract class GuzzleClient implements ApiInterface
{
    use CastToTrait;

    const STATUS_CODE_OK                = 200;
    const STATUS_CODE_TOO_MANY_REQUESTS = 429;

    /**
     * Instance of the Guzzle HTTP Client
     *
     * @var GuzzleClientInterface
     */
    protected $client;

    /**
     * @var int
     */
    protected $rateLimit;

    /**
     * @var int
     */
    protected $rateLimitRemaining;

    /**
     * @var int
     */
    protected $retryAfterSeconds;

    /**
     * @var string
     */
    protected $apiName;

    /**
     * @var int
     */
    protected $startTime;

    /**
     * @var GuzzleNotifier
     */
    protected $guzzleNotifier;

    /**
     * Runtime configuration parameters
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor.
     *
     * @since 0.1.0
     *
     * @param array $config
     * @param GuzzleClientInterface $client
     * @param GuzzleNotifier $guzzleNotifier Error and log handler
     */
    public function __construct(array $config, GuzzleClientInterface $client = null, GuzzleNotifier $guzzleNotifier)
    {
        $this->config  = $config;
        $this->client  = isset($client) ? $client : new Client();
        $this->apiName = $config['apiName'];

        $this->guzzleNotifier = $guzzleNotifier;
        $this->guzzleNotifier->init($config['apiName'], $config['moduleName']);
    }

    /**
     * Do a GET request.
     *
     * @since 0.1.0
     *
     * @param string $endpoint
     *
     * @return array|null
     */
    public function get($endpoint)
    {
        return $this->doRequest('GET', $endpoint);
    }

    /**
     * Do a POST request.
     *
     * @since 0.1.0
     *
     * @param string $endpoint
     *
     * @return array|null
     */
    public function post($endpoint)
    {
        return $this->doRequest('POST', $endpoint);
    }

    /*******************************************
     * Helpers
     ******************************************/

    /**
     * Do the HTTP Request.
     *
     * @since 0.1.0
     *
     * @param string $httpMethod HTTP method
     * @param string $endpoint URI endpoint for the new request
     * @param array $options (Optional) Request options.
     *
     * @return array|null
     * @throws ApiServerException
     * @throws ApiException
     */
    protected function doRequest($httpMethod, $endpoint, array $options = [])
    {
        try {
            $response = $this->client->request(
                $httpMethod,
                $endpoint,
                $this->mergeOptionsWithAuth($options)
            );

            $this->guzzleNotifier->logHttpSuccess($response, $endpoint);
            return $response->getBody()->getContents();
        } catch (ClientException $error) {
            if ($this->is429($error)) {
                return $this->handleRateLimit($error->getResponse(), $httpMethod, $endpoint, $options);
            }
            $this->guzzleNotifier->handleException($error, $options);
        } catch (Exception $error) {
            $this->guzzleNotifier->handleException($error, $options);
        }
    }

    /**
     * Checks if this is a 429 status error, meaning we hit the rate limit (throttle).
     *
     * @since 0.1.0
     *
     * @param ClientException $error
     *
     * @return bool
     */
    protected function is429(ClientException $error)
    {
        if (!$error->hasResponse()) {
            return false;
        }

        return (self::STATUS_CODE_TOO_MANY_REQUESTS === $error->getResponse()->getStatusCode());
    }

    /**
     * Got a 400 error. Handle the rate limit.  If it's a 429, then add the "delay" option for the amount of
     * time passed back to us from Help Desk Provider.
     *
     * @since 0.1.0
     *
     * @param $response
     * @param string $httpMethod
     * @param string $endpoint
     * @param array $options
     *
     * @return array|null|void
     */
    protected function handleRateLimit($response, $httpMethod, $endpoint, array $options)
    {
        $this->setRateLimit($response);

        // @link http://docs.guzzlephp.org/en/stable/request-options.html#delay
        $options['delay'] = ($this->retryAfterSeconds ? $this->retryAfterSeconds : 60) * 1000;

        $this->guzzleNotifier->logHitRateLimit($options['delay'], $this->retryAfterSeconds);

        // Redo the request.  Guzzle will wait per the delay time before resending.
        return $this->doRequest(
            $httpMethod,
            $endpoint,
            $options
        );
    }

    /**
     * Sets the Header Rate Limit/Throttle Values.
     *
     * @since 0.1.0
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function setRateLimit(ResponseInterface $response)
    {
        if (!$this->rateLimit) {
            $this->rateLimit = $this->getRateValue('X-RATE-LIMIT', $response);
        }
        $this->rateLimitRemaining = $this->getRateValue('X-RATE-LIMIT-REMAINING', $response);
        // If we get a 429 code, that means we've hit the rate limit.
        // Grab the Retry-After seconds, which now will be our wait/delay time before
        // the next request.
        $this->retryAfterSeconds = $response->getStatusCode() == self::STATUS_CODE_TOO_MANY_REQUESTS
            ? $this->getRateValue('Retry-After', $response)
            : null;
    }

    /**
     * The rate limit (throttle) values are held in an array within the response header. Let's handle the process of:
     *
     * 1. Checking if it was passed back to us.
     * 2. Getting it out of the response header.
     * 3. Unpacking it from the array.
     * 4. Type casting it to an integer.
     *
     * @since 0.1.0
     *
     * @param string $key Rate value to get from the response header
     * @param ResponseInterface $response
     *
     * @return int
     */
    protected function getRateValue($key, ResponseInterface $response)
    {
        if (!$response->hasHeader($key)) {
            return null;
        }
        $value = $response->getHeader($key);
        $value = is_array($value) ? $value[0] : $value;
        $value = $value ?: 0;
        return (int)$value;
    }

    /**
     * Get the request options with the basic authorization.
     *
     * @since 0.1.0
     *
     * @param array $options
     *
     * @return array
     */
    protected function mergeOptionsWithAuth(array $options)
    {
        return array_merge($options, [
            'auth' => [
                $this->config['username'] . '/token',
                $this->config['token'],
                'basic',
            ],
        ]);
    }
}
