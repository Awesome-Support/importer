<?php

namespace Pressware\AwesomeSupport\API\Provider\HelpScout;

use Pressware\AwesomeSupport\API\Abstracts\ProviderController;

class ApiController extends ProviderController
{
    /**
     * Get all of the mailboxes.
     *
     * @since 0.1.0
     *
     * @return array
     * @link https://developer.helpscout.com/mailbox-api/endpoints/mailboxes/list/
     */
    public function getMailboxes()
    {
        $selectOptions = [];
        $endpoint      = 'https://api.helpscout.net//v2/mailboxes';
        $json          = $this->get($endpoint);
        $_embedded = $this->fromJSON($json)->_embedded;
        foreach ($_embedded->mailboxes as $mailbox) {
            $selectOptions[$mailbox->id] = $mailbox->name;
        }
        return $selectOptions;
    }

    /*******************************************
     * Helpers
     ******************************************/

    /**
     * Request the Tickets (conversations) from Help Scout.
     *
     * This is a multi-step process of
     *      1. Request a list of conversations
     *      2. Iterate that list and request its conversations packet (object)
     *      3. Repeat if there are more pages of conversations
     *
     * @since 0.1.0
     *
     * @return void
     */
    protected function request()
    {
        $pageNumber = '';
        do {
            $endpoint = $this->getEndpoint($pageNumber);
            $packet   = $this->fromJSON($this->get($endpoint));
            $_embedded = $packet->_embedded;

            // No conversations. We're done.
            if (!$packet->page->totalElements) {
                break;
            }

            // Process each of the items and request each conversation object.
            foreach ($_embedded->conversations as $item) {
                $this->requestConversation((int)$item->id);
            }
            // continue iterating until there are no more pages to fetch.
        } while ($packet->page->number < $packet->page->totalPages);
    }

    /**
     * Request and map the conversation for the specific Conversation ID from Help Scout.
     *
     * @since 0.1.0
     *
     * @param int|string $conversationId
     *
     * @return void
     * @link https://developer.helpscout.com/mailbox-api/endpoints/conversations/get/
     */
    protected function requestConversation($conversationId)
    {
        $endpoint = "https://api.helpscout.net/v2/conversations/{$conversationId}";
        $packet   = $this->get($endpoint);
        $this->dataMapper->mapJSON($packet);
    }

    /**
     * Get the conversions endpoint.
     *
     * @since 0.1.0
     *
     * @param int|null $nextPage If provided, appends the `page={$nextPage}` query var to endpoint
     *
     * @return string
     * @link https://developer.helpscout.com/mailbox-api/endpoints/conversations/list/
     */
    protected function getEndpoint($nextPage = null)
    {
        $endpoint = sprintf(
            'https://api.helpscout.net/v2/conversations?mailbox=%s',
            $this->config['mailboxId']
        );

        // If date limited, append the start time to the endpoint.
        $startTime = $this->config['startDate'] ? $this->getStartTime() : '';
        if ($startTime) {
            $endpoint .= "&modifiedSince={$startTime}";
        }

        // If a page number is provided, append the `page={$nextPage}` to endpoint.
        if ($nextPage) {
            $endpoint .= "&page={$nextPage}";
        }

        return $endpoint;
    }


    /**
     * Get the start date/time.  Help Scout requires this format: 2017-10-31T16:54:00Z.
     *
     * @since 0.1.0
     *
     * @return false|int
     */
    protected function getStartTime()
    {
        if (!$this->startTime) {
            $date            = $this->config['startDate'] ?: '-1 year';
            $this->startTime = (new \DateTime($date))->format('Y-m-dTH:i:s');
            $this->startTime = str_replace('UTC', 'T', $this->startTime) . 'Z';
        }
        return $this->startTime;
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
            'headers' => $this->config['headers'],
        ]);
    }
}
