<?php

namespace Pressware\AwesomeSupport\API\Provider\Zendesk;

use Pressware\AwesomeSupport\API\Abstracts\ProviderController;

class ApiController extends ProviderController
{
    const MAX_PACKET_SIZE = 1000;

    /**
     * @var string
     */
    protected $subdomain;

    /*******************************************
     * Helpers
     ******************************************/

    /**
     * Request the Tickets from Help Scout.
     *
     * @since 0.1.0
     *
     * @return void
     */
    protected function request()
    {
        // Initialize
        $this->jsonResponses = [
            'tickets'      => [],
            'ticketEvents' => [],
        ];

        foreach (['tickets', 'ticketEvents'] as $whichEndpoint) {
            $this->requestPackets($whichEndpoint);

            foreach ($this->jsonResponses[$whichEndpoint] as $json) {
                $this->dataMapper->mapJSON($json, $whichEndpoint);
            }
        }
    }

    protected function requestPackets($whichEndpoint)
    {
        $nextPage = '';
        do {
            $packet                                = $this->get(
                $this->getEndpoint($whichEndpoint, $nextPage)
            );
            $this->jsonResponses[$whichEndpoint][] = $packet;

            // Unpack to get the next page and count.
            $packet   = $this->fromJSON($packet);
            $nextPage = $packet->next_page;
        } while ($packet->count > self::MAX_PACKET_SIZE);
    }

    /**
     * Get the endpoint for the selected task, i.e. tickets or ticket events.
     *
     * >_For Tickets, the endpoint is side loaded with users and the comment count.
     * @link https://developer.zendesk.com/rest_api/docs/core/incremental_export#incremental-ticket-export
     *
     * >_For Ticket Events, the endpoint is side loaded with comment events.
     * @link https://developer.zendesk.com/rest_api/docs/core/incremental_export#incremental-ticket-event-export
     *
     * @param string $whichEndpoint Tickets or Events.
     * @param string $endpoint (Optional) When passed in, just this endpoint
     *                          as it's the next page endpoint.
     *
     * @return string
     */
    protected function getEndpoint($whichEndpoint, $endpoint = '')
    {
        if ($endpoint) {
            return $endpoint;
        }
        $pattern = 'https://%s.zendesk.com/api/v2/incremental/';
        $pattern .= 'ticketEvents' === $whichEndpoint
            ? 'ticket_events.json?start_time=%s&include=comment_events'
            : 'tickets.json?start_time=%s&include=users,comment_count';
        return sprintf(
            $pattern,
            $this->subdomain,
            $this->getStartTime()
        );
    }
}
