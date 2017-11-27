<?php

namespace Pressware\AwesomeSupport\API\Abstracts;

use GuzzleHttp\ClientInterface;
use Pressware\AwesomeSupport\API\Contracts\DataMapperInterface;
use Pressware\AwesomeSupport\Traits\CastToTrait;

abstract class ProviderController extends GuzzleClient
{
    use CastToTrait;

    /**
     * @var DataMapperInterface
     */
    protected $dataMapper;

    /**
     * JSON responses from the API.
     *
     * @var array
     */
    protected $jsonResponses;

    /**
     * ApiController constructor.
     *
     * @param array $config
     * @param ClientInterface $client
     * @param DataMapperInterface $dataMapper
     * @param GuzzleNotifier $guzzleNotifier
     */
    public function __construct(
        array $config,
        ClientInterface $client,
        DataMapperInterface $dataMapper,
        GuzzleNotifier $guzzleNotifier
    ) {
        $config['moduleName'] = get_called_class();
        parent::__construct($config, $client, $guzzleNotifier);
        $this->dataMapper = $dataMapper;
        if (property_exists($this, 'subdomain')) {
            $this->subdomain = $config['subdomain'];
        }
    }

    /**
     * Let the magic happen.
     *
     * @since 0.1.0
     *
     * @return array|void
     */
    public function getTickets()
    {
        $this->guzzleNotifier->logStarting($this->config);
        $this->dataMapper->clearAllRepositories();
        $this->request();
        $tickets = $this->dataMapper->assemble();
        $this->guzzleNotifier->logFinished($tickets);
        return $tickets;
    }

    /*******************************************
     * Helpers
     ******************************************/

    /**
     * Process the request for tickets and map to the dataMapper.
     *
     * @since 0.1.0
     *
     * @return void
     */
    abstract protected function request();

    /**
     * Get the start time.
     *
     * @since 0.1.0
     *
     * @return false|int
     */
    protected function getStartTime()
    {
        if (!$this->startTime) {
            $date            = $this->config['startDate'] ?: '-1 year';
            $this->startTime = strtotime(
                (new \DateTime($date))->format('d-m-Y')
            );
        }
        return $this->startTime;
    }
}
