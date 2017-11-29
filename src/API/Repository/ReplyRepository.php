<?php

namespace Pressware\AwesomeSupport\API\Repository;

use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

class ReplyRepository extends Repository
{
    /**
     * Creates a new repository.
     *
     * @param NotificationInterface $notifier Error and log handler.
     * @param array $items
     */
    public function __construct(NotificationInterface $notifier, array $items = [])
    {
        parent::__construct($notifier);
        foreach ($items as $replyId => $reply) {
            if (!is_array($reply)) {
                continue;
            }
            $ticketId                         = $reply['ticketId'];
            $this->items[$ticketId][$replyId] = $this->mergeDefaults($ticketId, $reply);
        }
    }

    /**
     * Create a reply in the repository which is stored by {$ticketId}.{$replyId}.
     *
     * @since 0.1.0
     *
     * @param int|string $ticketId
     * @param int|string $replyId
     * @param array $item
     *
     * @return void
     */
    public function create($ticketId, $replyId, array $item)
    {
        $ticketId = (int)$ticketId;
        $this->initTicket($ticketId);

        $item = $this->mergeDefaults($ticketId, $item);

        $this->set("{$ticketId}.{$replyId}", $item);
    }

    /**
     * Set the attachment for a specific ticket's reply.
     *
     * @since 0.1.0
     *
     * @param int|string $ticketId
     * @param int|string $replyId
     * @param array $attachment
     *
     * @return void
     */
    public function setAttachment($ticketId, $replyId, array $attachment)
    {
        if (!$this->has($ticketId) || !$this->has("{$ticketId}.{$replyId}")) {
            return $this->create($ticketId, $replyId, ['attachments' => [$attachment]]);
        }

        if (!is_array($this->items[$ticketId][$replyId]['attachments'])) {
            $this->items[$ticketId][$replyId]['attachments'] = [];
        }

        // Avoid duplicates by checking if this attachment already exists.
        foreach ($this->items[$ticketId][$replyId]['attachments'] as $storedAttachment) {
            if ($storedAttachment['url'] === $attachment['url']) {
                return;
            }
        }

        $this->items[$ticketId][$replyId]['attachments'][] = $attachment;
    }

    /*******************************************
     * Helpers
     ******************************************/

    /**
     * Initialize an empty array for this ticket ID, if it does not
     * already exists in the repository.
     *
     * @since 0.1.0
     *
     * @param $ticketId
     *
     * @return void
     */
    protected function initTicket($ticketId)
    {
        if (!$this->has($ticketId)) {
            $this->set($ticketId, []);
        }
    }

    /**
     * Merge with the default data structure to ensure all properties are present.
     *
     * @since 0.1.0
     *
     * @param int|string $ticketId
     * @param array $reply
     *
     * @return array
     */
    protected function mergeDefaults($ticketId, array $reply)
    {
        return array_merge(
            [
                'ticketId'    => $ticketId,
                'userId'      => 0,
                'reply'       => 0,
                'timestamp'   => '',
                'read'        => false,
                'attachments' => [],
            ],
            $reply
        );
    }
}
