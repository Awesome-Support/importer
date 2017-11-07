<?php

namespace Pressware\AwesomeSupport\API;

use Pressware\AwesomeSupport\API\Contracts\ApiInterface;
use Pressware\AwesomeSupport\API\Contracts\ApiManagerInterface;
use Pressware\AwesomeSupport\API\Exception\ApiNotAvailable;
use Pressware\AwesomeSupport\API\Provider\GuzzleHttpClient;
use Pressware\AwesomeSupport\API\Provider\HelpScout\MailboxSubscriber;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;
use Pressware\AwesomeSupport\Traits\CastToTrait;

class ApiManager implements ApiManagerInterface
{
    use CastToTrait;

    /**
     * @var string
     */
    protected $optionsPrefix;

    /**
     * Available help desk providers.
     *
     * @var array
     */
    protected $helpDeskProviders = [];

    /**
     * @var NotificationInterface
     */
    protected $notifier;

    /**
     * Cached API Controller
     * @var
     */
    protected $api;

    /**
     * The currently cached API Controller.
     * @var string
     */
    protected $currentAPI;

    /**
     * @var array
     */
    protected $config;

    /**
     * ApiManager constructor.
     *
     * @param array $config Runtime configuration parameters
     * @param $notifier $notification Error and Logging Handler
     */
    public function __construct(array $config, NotificationInterface $notifier)
    {
        $this->config   = $config;
        $this->notifier = $notifier;
        foreach ($config as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * Creates the Help Scout Mailbox Subscriber.
     *
     * @since 0.1.0
     *
     * @return MailboxSubscriber
     */
    public function createHelpScoutMailboxSubscriber()
    {
        return new MailboxSubscriber($this->config, $this, $this->notifier);
    }

    /**
     * Get the selected API.
     *
     * @since 0.1.0
     *
     * @param string $requestedApi
     * @param array $data Runtime configuration parameters
     *
     * @return bool|ApiInterface
     * @throws ApiNotAvailable
     */
    public function getApi($requestedApi, array $data)
    {
        if ($this->currentAPI === $requestedApi && $this->api) {
            return $this->api;
        }

        // Get the service provider's class name
        $apiServiceProvider = $this->getClassname($requestedApi);
        if (!$apiServiceProvider) {
            throw new ApiNotAvailable($requestedApi);
        }

        // Create the API Controller.
        $this->api = (new $apiServiceProvider($this->config))
            ->create($this->initData($data), $this->notifier);

        $this->currentAPI = $requestedApi;
        return $this->api;
    }

    /**
     * Lookup the API's Class name.
     *
     * @since 0.1.0
     *
     * @param string $selectedApi
     *
     * @return string|null
     */
    protected function getClassname($selectedApi)
    {
        $classes = [
            'zendesk'    => '\Provider\Zendesk\ServiceProvider',
            'help-scout' => '\Provider\HelpScout\ServiceProvider',
            'ticksy'     => '\Provider\Ticksy\ServiceProvider',
        ];

        if (array_key_exists($selectedApi, $classes)) {
            return __NAMESPACE__ . $classes[$selectedApi];
        }
    }

    /**
     * Initialize the data packet to be spent to the Help Desk Provider (i.e. runtime configuration parameters).
     *
     * Dates are passed without times.  Initialize both dates with the appropriate beginning or ending timestamp.
     *
     * @since 0.1.0
     *
     * @param array $data
     *
     * @return void
     */
    protected function initData(array $data)
    {
        $data['optionsPrefix'] = $this->optionsPrefix;

        $times = [
            'date-start' => ' 00:00:00',
            'date-end'   => ' 23:59:59',
        ];

        foreach ($times as $key => $time) {
            $key = $this->optionsPrefix . $key;
            if (array_key_exists($key, $data) && $data[$key]) {
                $data[$key] = $this->toFormattedDate($data[$key], 'Y-m-d');
                $data[$key] .= $time;
            }
        }

        return $data;
    }
}
