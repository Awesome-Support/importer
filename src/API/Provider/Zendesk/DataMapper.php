<?php

namespace Pressware\AwesomeSupport\API\Provider\Zendesk;

use Pressware\AwesomeSupport\API\Abstracts\DataMapper as AbstractDataMapper;

class DataMapper extends AbstractDataMapper
{
    /**
     * @var array
     */
    protected $attachments = [];

    /**
     * Maps the incoming JSON to the individual repositories.
     *
     * @since 0.1.0
     *
     * @param string|array $json
     * @param string $key (Optional)
     *
     * @return void
     */
    public function mapJSON($json, $key = '')
    {
        $packets = $this->fromJSON($json);

        if ('tickets' === $key) {
            $this->mapUsers($packets->users);
            $this->mapTickets($packets->tickets);
        }

        if ('ticketEvents' === $key) {
            $this->mapEvents($packets->ticket_events);
        }
    }

    /**
     * Maps the users into the User Repository.
     *
     * @since 0.1.0
     *
     * @param array $users
     *
     * @return void
     */
    protected function mapUsers(array $users)
    {
        foreach ($users as $user) {
            $this->userRepository->createModel($user);
        }
    }

    /**
     * Map the response into the Ticket Repository.
     *
     * @since 0.1.0
     *
     * @param array $tickets
     *
     * @return void
     */
    protected function mapTickets(array $tickets)
    {
        foreach ($tickets as $ticket) {
            $updatedAt = $this->toFormattedDate($ticket->updated_at);
            // Skip this one if it's after the end date selection.
            if (!$this->withinDateRange($updatedAt, false)) {
                continue;
            }

            $this->ticketRepository->create(
                (int)$ticket->id,
                [
                    'agentID'     => $ticket->assignee_id,
                    'customerID'  => $ticket->requester_id,
                    'subject'     => $ticket->subject,
                    'description' => $ticket->description,
                    'createdAt'   => $this->toFormattedDate($ticket->created_at),
                    'updatedAt'   => $this->toFormattedDate($updatedAt),
                ]
            );
        }
    }

    /**
     * Maps the incoming JSON to the individual repositories.
     *
     * @since 0.1.0
     *
     * @param array $ticketEvents
     *
     * @return void
     */
    protected function mapEvents(array $ticketEvents)
    {
        foreach ($ticketEvents as $ticketEvent) {
            $data = [
                'id'               => $ticketEvent->id,
                'ticketId'         => $ticketEvent->ticket_id,
                'timestamp'        => $ticketEvent->timestamp,
                'date'             => $this->toFormattedDate($ticketEvent->created_at),
                'updaterId'        => $ticketEvent->updater_id,
                'isOriginalTicket' => false,
                'requesterId'      => 0,
                'reply'            => null,
                'replyId'          => 0,
                'attachments'      => null,
            ];

            // Psst...using a closure to pass $data byRef.
            array_walk($ticketEvent->child_events, function ($childEvent) use (&$data) {
                if (!isset($childEvent->public) || $childEvent->public) {
                    $this->mapChildEvent($childEvent, $data);
                }
            });

            // If there's a reply, then let's process it.
            if ($data['reply']) {
                $this->mapReplyOrTicket($data);
            }
        }
    }

    /**
     * Process the event.  Events include Replies/Comments,
     * Status changing, and many others.
     *
     * See the link for the details.
     *
     * @since 0.2.0
     *
     * @param \stdClass $event
     * @param array $data Array of arguments
     *
     * @return array
     */
    protected function mapChildEvent(\stdClass $event, array &$data)
    {
        if ('Comment' === $event->event_type) {
            $data['date'] = $this->toFormattedDate($event->created_at);

            $data['replyId'] = $event->id;
            $data['reply']   = [
                'userId'    => $event->author_id,
                'reply'     => $event->body,
                'timestamp' => $data['date'],
            ];
            if ($this->hasAttachments($event)) {
                $data['attachments'] = $event->attachments;
            }
            return;
        }

        if ('Create' === $event->event_type && isset($event->requester_id)) {
            $data['requesterId'] = $event->requester_id;
        }

        if (isset($event->status)) {
            $this->historyRepository->create(
                $data['ticketId'],
                $data['requesterId'] ?: $data['updaterId'],
                $this->getHistoryStatus($event->status),
                $data['date'], 
                $data['id']
            );

            if ('open' === $event->status) {
                $data['isOriginalTicket'] = true;
            }
            $data['requesterId'] = 0;
        }
    }

    /**
     * Map the reply/comment for this ticket.
     *
     * Ticket child events are broken up into individual event elements.
     * Therefore, we have to process all of the child events first for a given
     * ticket event.  If we flagged it as a rely, then we pull it together here.
     *
     * @since 0.1.0
     *
     * @param array $data
     *
     * @return void
     */
    protected function mapReplyOrTicket(array $data)
    {
        if ($data['isOriginalTicket'] && isset($data['attachments'])) {
            $this->mapAttachments($data['attachments'], $data['ticketId']);
            return;
        }

        if ($data['requesterId']) {
            $data['reply']['userId'] = $data['requesterId'];
        }

        $this->replyRepository->create($data['ticketId'], $data['replyId'], $data['reply']);
        if (isset($data['attachments'])) {
            $this->mapAttachments($data['attachments'], $data['ticketId'], $data['replyId']);
        }
    }

    /**
     * Map the attachment and convert into an array data structure.
     *
     * @since 0.1.0
     *
     * @param \stdClass|mixed $attachment
     *
     * @return array
     * [
     *      'url' => 'holds a valid URL',
     *      'filename' => 'holds the filename, e.g. image.jpg',
     * ]
     * @link https://developer.zendesk.com/rest_api/docs/core/attachments
     */
    public function mapAttachment($attachment)
    {
        return [
            'url'      => $attachment->content_url,
            'filename' => $this->getAttachmentFilename($attachment->content_url),
        ];
    }

    protected function getAttachmentFilename($attachmentUrl)
    {
        $urlQuery = parse_url($attachmentUrl, PHP_URL_QUERY);
        return str_replace('name=', '', $urlQuery);
    }

    /**
     * Checks if this event has attachments.
     *
     * @since 0.1.0
     *
     * @param \stdClass $event
     *
     * @return bool
     */
    protected function hasAttachments($event)
    {
        return (
            property_exists($event, 'attachments') &&
            is_array($event->attachments) &&
            !empty($event->attachments)
        );
    }
}
