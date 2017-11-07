<?php

namespace Pressware\AwesomeSupport\API\Provider\Ticksy;

use Pressware\AwesomeSupport\API\Abstracts\ProviderController;

class ApiController extends ProviderController
{
    const NO_TICKETS_RESPONSE = -1;

    /**
     * @var string
     */
    protected $subdomain;

    /*******************************************
     * Helpers
     ******************************************/

    /**
     * Request the Tickets from Ticksy.
     *
     * @since 0.1.0
     *
     * @return void
     */
    protected function request()
    {
        foreach (['open', 'closed'] as $getRequest) {
            $this->requestTickets($getRequest);
        }
    }

    /**
     * Request Tickets.  Ticksy sends 100 tickets per request.  We then append /pageNumber
     * to the endpoint on each iteration until the Data Mapper returns a -1, indicating
     * no tickets received.
     *
     * @since 0.1.0
     *
     * @param string $getRequest
     *
     * @return void
     */
    protected function requestTickets($getRequest)
    {
        $pageNumber = 0;
        do {
            $endpoint = $this->getEndpoint($getRequest, $pageNumber);
            $packet   = $this->get($endpoint);

            if (!$this->hasTickets($packet, $getRequest)) {
                break;
            }

            $response = $this->dataMapper->mapJSON(
                $packet,
                "{$getRequest}-tickets"
            );
            $pageNumber++;
        } while ($response != self::NO_TICKETS_RESPONSE);
    }

    /**
     * Checks if this packet has tickets in it.
     *
     * @since 0.1.0
     *
     * @param string $packet
     * @param string $getRequest
     *
     * @return bool
     */
    protected function hasTickets($packet, $getRequest)
    {
        $dataset = $this->fromJSON($packet);
        if (!$dataset || !is_object($dataset)) {
            return false;
        }

        $property = "{$getRequest}-tickets";
        if (!property_exists($dataset, $property)) {
            return false;
        }

        return !empty($dataset->$property);
    }

    /**
     * Request Tickets from Ticksy.
     *
     * @param string $status (optional) 'closed' or 'open'
     * @param string $pageNumber
     *
     * @return \stdClass
     * @link https://ticksy.com/api/system-get-endpoints/
     */
    protected function getEndpoint($status = 'open', $pageNumber = '')
    {
        $endpoint = sprintf(
            'https://api.ticksy.com/v1/%s/%s/%s-tickets.json',
            $this->subdomain,
            $this->config['token'],
            $status
        );

        // Additional pages are requested by adding another parameter, e.g.
        // Page 2 is /2
        if ($pageNumber) {
            $endpoint .= "/{$pageNumber}";
        }

        return $endpoint;
    }

    /**
     * Ticksy doesn't require Basic Auth.  Just return the options.
     *
     * @since 0.1.0
     *
     * @param array $options
     *
     * @return array
     */
    protected function mergeOptionsWithAuth(array $options)
    {
        return $options;
    }
}
