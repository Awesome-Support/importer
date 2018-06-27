<?php

namespace Pressware\AwesomeSupport\API\Repository;

use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

class TicketRepository extends Repository
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
        foreach ($items as $ticketId => $ticket) {
            if (!is_array($ticket)) {
                continue;
            }
            $this->items[$ticketId] = $this->mergeDefaults($ticketId, $ticket);
        }
    }

    /**
     * Create the ticket's data model and store into the repository.
     *
     * @since 0.1.0
     *
     * @param int|string $ticketId
     * @param array $ticket
     *
     * @return void
     */
    public function create($ticketId, array $ticket)
    {
        $ticketId = (int)$ticketId;

        $ticket = $this->mergeDefaults($ticketId, $ticket);

        $this->set($ticketId, $ticket);
    }

    /**
     * Set the description for a specific ticket.
     *
     * @since 0.1.0
     *
     * @param int|string $ticketId
     * @param string $description
     *
     * @return void
     */
    public function setDescription($ticketId, $description)
    {
        if (!$this->has($ticketId)) {
            return $this->create($ticketId, ['description' => $description]);
        }

        $this->set("{$ticketId}.description", $description);
    }

    /**
     * Set the attachment for a specific ticket.
     *
     * @since 0.1.0
     *
     * @param int|string $ticketId
     * @param array $attachment
     *
     * @return void
     */
    public function setAttachment($ticketId, array $attachment)
    {
        if (!$this->has($ticketId)) {
            return $this->create(
                $ticketId,
                [
                    'attachments' => [$attachment],
                ]
            );
        }

        if (!is_array($this->items[$ticketId]['attachments'])) {
            $this->items[$ticketId]['attachments'] = [];
        }

        // Avoid duplicates by checking if this attachment already exists.
        foreach ($this->items[$ticketId]['attachments'] as $storedAttachment) {
            if ($storedAttachment['url'] === $attachment['url']) {
                return;
            }
        }

        $this->items[$ticketId]['attachments'][] = $attachment;
    }

    /*******************************************
     * Helpers
     ******************************************/

    /**
     * Merge with the default data structure to ensure all properties are present.
     *
     * @since 0.1.0
     *
     * @param int|string $ticketId
     * @param array $ticket
     *
     * @return array
     */
    protected function mergeDefaults($ticketId, $ticket)
    {
        return array_merge(
            [
                'ticketId'    => $ticketId,
                'agentID'     => 0,
                'customerID'  => 0,
                'subject'     => '',
                'description' => '',
                'attachments' => null,
                'createdAt'   => '',
                'updatedAt'   => ''
            ],
            $ticket
        );
    }
}
