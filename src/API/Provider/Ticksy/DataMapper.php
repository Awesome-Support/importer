<?php

namespace Pressware\AwesomeSupport\API\Provider\Ticksy;

use Pressware\AwesomeSupport\API\Abstracts\DataMapper as AbstractDataMapper;

class DataMapper extends AbstractDataMapper
{
    const NO_TICKETS_RESPONSE = -1;

    /**
     * Maps the incoming JSON to the individual repositories.
     *
     * @since 0.1.2
     *
     * @param string $json
     * @param string $key (Optional)
     *
     * @return bool|void
     */
    public function mapJSON($json, $key = '')
    {
        $tickets = $this->fromJSON($json)->{$key};
        if (!$tickets) {
            return self::NO_TICKETS_RESPONSE;
        }

        foreach ((array)$tickets as $ticket) {
            if (!$this->withinDateRange($ticket->time_stamp, true)) {
                continue;
            }

            $ticketId = (int)$ticket->ticket_id;

            // Store the ticket
            $this->ticketRepository->create($ticketId, [
                'agentID'    => (int)$ticket->assigned_to,
                'customerID' => (int)$ticket->user_id,
                'subject'    => $ticket->ticket_title,
                'createdAt'  => $ticket->time_stamp,
            ]);

            // Store the agent.
            if ($ticket->assigned_to) {
                $this->storeUser(
                    $ticket->assigned_to,
                    $ticket->assigned_to_name,
                    $ticket->assigned_to_email,
                    'agent'
                );
            }

            // Store the ticket's current status.
            $this->historyRepository->create(
                $ticketId,
                $ticket->user_id,
                $ticket->status,
                $ticket->time_stamp
            );

            $ticketComments = (array)$ticket->ticket_comments;
            $this->mapOriginalTicket($ticketId, $ticketComments);
            if (!empty($ticketComments)) {
                $this->mapReplies($ticketId, $ticketComments);
            }
        }
    }

    /**
     * If there are attachments, map them to the appropriate repository.
     *
     * @since 0.1.2
     *
     * @param int $ticketId Ticket ID
     * @param \stdClass $comment The ticket/reply's comment object
     * @param int $replyId (Optional) Reply ID, when it's a reply
     *
     * @return void
     */
    protected function mapCommentAttachments($ticketId, \stdClass $comment, $replyId = 0)
    {
        if (!property_exists($comment, 'attachments') && $comment->attachments) {
            return;
        }

        $this->mapAttachments((array)$comment->attachments, $ticketId, $replyId);
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
     */
    public function mapAttachment($attachment)
    {
        return [
            'url'      => $attachment->file_url,
            'filename' => $attachment->file_name,
        ];
    }

    /**
     * Map the original ticket.
     *
     * @since 0.1.2
     *
     * @param int $ticketId
     * @param array $ticketComments
     *
     * @return void
     */
    protected function mapOriginalTicket($ticketId, array &$ticketComments)
    {
        // The original ticket is the last one in the Ticket Comments array.
        // Pop it off the array.
        $originalTicket = array_pop($ticketComments);

        // Store the ticket user.
        $this->storeUser(
            $originalTicket->user_id,
            $originalTicket->commenter_name,
            $originalTicket->commenter_email,
            $originalTicket->user_type
        );

        // Store the original ticket's comment
        $this->ticketRepository->set("{$ticketId}.customerID", (int)$originalTicket->user_id);
        $this->ticketRepository->setDescription($ticketId, html_entity_decode($originalTicket->comment));
        $this->mapCommentAttachments($ticketId, $originalTicket);

        if (empty($ticketComments)) {
            return;
        }

        // If there are still comments, grab the updateAt timestamp of the 1st in the array.
        reset($ticketComments);
        $lastComment = current($ticketComments);
        $this->ticketRepository->set("{$ticketId}.updatedAt", $lastComment->time_stamp);
    }

    /**
     * Map the replies.
     *
     * @since 0.2.0
     *
     * @param int $ticketId
     * @param array $ticketComments
     *
     * @return void
     */
    protected function mapReplies($ticketId, array $ticketComments)
    {
        foreach ((array)$ticketComments as $item) {
            if ('comment' !== $item->type) {
                continue;
            }

            $this->storeUser(
                $item->user_id,
                $item->commenter_name,
                $item->commenter_email,
                $item->user_type
            );

            $this->replyRepository->create(
                $ticketId,
                $item->comment_id,
                [
                    'userId'    => $item->user_id,
                    'reply'     => html_entity_decode($item->comment),
                    'timestamp' => $item->time_stamp,
                ]
            );

            $this->mapCommentAttachments($ticketId, $item, $item->comment_id);
        }
    }
}
