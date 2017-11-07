<?php

namespace Pressware\AwesomeSupport\API\Provider\Ticksy;

use GuzzleHttp\Client;
use Pressware\AwesomeSupport\API\Abstracts\GuzzleNotifier;
use Pressware\AwesomeSupport\API\AttachmentMapper;
use Pressware\AwesomeSupport\API\Repository\HistoryRepository;
use Pressware\AwesomeSupport\API\Repository\ReplyRepository;
use Pressware\AwesomeSupport\API\Repository\TicketRepository;
use Pressware\AwesomeSupport\API\Repository\UserRepository;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

/**
 * Class ServiceProvider
 * @package Pressware\AwesomeSupport\API\Provider\Zendesk
 *
 * The job of this ServiceProvider is to create API's Controller, Data Mapper,
 * and each of the required dependencies.  The code here may be redundant to the
 * other API ServiceProviders; however, we provide flexibility for the differences
 * and any future changes that may occur from Ticksy.
 */
class ServiceProvider
{
    protected $pluginPath;

    public function __construct(array $config)
    {
        $this->pluginPath = $config['pluginPath'];
    }

    /**
     * Creates each dependency and the API controller.
     *
     * @since 0.1.0
     *
     * @param array $data Runtime configuration parameters
     * @param NotificationInterface $notifier Error and log handler
     *
     * @return ApiController
     */
    public function create(array $data, NotificationInterface $notifier)
    {
        $config = $this->remapData($data);

        return new ApiController(
            $config,
            $this->createGuzzleClient(),
            $this->createDataMapper($config, $notifier),
            new GuzzleNotifier($notifier)
        );
    }

    /**
     * Remaps the dataset to the required configuration format.
     *
     * @since 0.1.0
     *
     * @param array $data
     *
     * @return array
     */
    protected function remapData(array $data)
    {
        $optionsPrefix = $data['optionsPrefix'];
        return [
            'apiName'   => 'Ticksy',
            'token'     => $data[$optionsPrefix . 'api-token'],
            'subdomain' => $data[$optionsPrefix . 'api-subdomain'],
            'startDate' => $data[$optionsPrefix . 'date-start'],
            'endDate'   => $data[$optionsPrefix . 'date-end'],
        ];
    }

    /**
     * Create the Data Mapper.
     *
     * @since 0.1.0
     *
     * @param array $config
     * @param NotificationInterface $notifier
     *
     * @return DataMapper
     */
    protected function createDataMapper(array $config, NotificationInterface $notifier)
    {
        $dataMapper = new DataMapper(
            new TicketRepository($notifier),
            new HistoryRepository($notifier),
            new ReplyRepository($notifier),
            new UserRepository($notifier),
            $this->createAttachmentMapper($notifier)
        );

        $dataMapper->init(
            [
                'startDate' => $config['startDate'],
                'endDate'   => $config['endDate'],
            ],
            'Ticksy API'
        );

        return $dataMapper;
    }

    protected function createGuzzleClient()
    {
        return new Client([
            'base_uri' => home_url('', 'https'),
        ]);
    }

    protected function createAttachmentMapper(NotificationInterface $notifier)
    {
        $config = require $this->pluginPath . 'config/attachmentMapper.php';
        return new AttachmentMapper($config, $notifier);
    }
}
