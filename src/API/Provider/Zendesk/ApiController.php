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
     * Request the Tickets from Zendesk.
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

        // Process each of the items and request each comment.
        foreach ($this->jsonResponses['tickets'] as $json) {
            $packet = $this->fromJSON($json);
            foreach ($packet->tickets as $ticket) {
                echo '<pre>Getting '.$ticket->comment_count.' comments for ticket '.$ticket->id.'</pre>';
                if ((int)$ticket->comment_count > 0 && $ticket->status != 'deleted') {
                    $this->requestComments($ticket->id);
                }
            }
        }
    }

    protected function requestPackets($whichEndpoint)
    {
        $nextPage = '';
        do {
            $packet = $this->get(
                $this->getEndpoint($whichEndpoint, $nextPage)
            );
            $this->jsonResponses[$whichEndpoint][] = $packet;

            // Unpack to get the next page and count.
            $packet = $this->fromJSON($packet);
            if ($nextPage == urldecode($packet->next_page)) {
                break;
            }
            $nextPage = urldecode($packet->next_page);
        } while ($packet->count >= self::MAX_PACKET_SIZE);
    }

    /**
     * Request and map the comments for the specific ticket ID from Zendesk.
     *
     * @since 0.1.0
     *
     * @param int|string $ticketId
     *
     * @return void
     */
    protected function requestComments($ticketId)
    {
        $pattern = 'https://%s.zendesk.com/api/v2/tickets/%d/comments.json?include=users';
        $url = sprintf(
            $pattern,
            $this->subdomain,
            $ticketId
        );

        $packet = $this->get($url);
        
        $this->dataMapper->mapJSON($packet, 'comments', $ticketId);
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
            ? 'ticket_events.json?start_time=%s'
            : 'tickets.json?start_time=%s&include=users,comment_count';
        $url = sprintf(
            $pattern,
            $this->subdomain,
            $this->getStartTime()
        );
        return $url;
    }
}
