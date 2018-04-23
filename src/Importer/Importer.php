<?php

namespace Pressware\AwesomeSupport\Importer;

use PharIo\Manifest\Email;
use Pressware\AwesomeSupport\Entity\Ticket;
use Pressware\AwesomeSupport\Entity\User;
use Pressware\AwesomeSupport\Importer\Exception\ImporterException;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;
use WP_Error;
use WP_User;

/**
 * Class Importer
 * @package Pressware\AwesomeSupport
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Importer implements ImporterInterface
{
    /**
     * @var NotificationInterface
     */
    protected $notifier;

    /**
     * @var Locator
     */
    protected $locator;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var Inserter
     */
    protected $inserter;

    /**
     * @var EmailSubscriber
     */
    protected $emailSubscriber;

    /**
     * @var int
     */
    protected $numberTickets = 0;

    /**
     * @var int
     */
    protected $numberReplies = 0;

    /**
     * @var int
     */
    protected $numberAttachments = 0;

    /**
     * @var WP_User
     */
    protected $currentUser;

    /**
     * Importer constructor.
     *
     * @param NotificationInterface $notifier
     * @param Locator $locator
     * @param Validator $validator
     * @param Inserter $inserter
     * @param EmailSubscriber $emailSubscriber
     */
    public function __construct(
        NotificationInterface $notifier,
        Locator $locator,
        Validator $validator,
        Inserter $inserter,
        EmailSubscriber $emailSubscriber
    ) {
        $this->notifier        = $notifier;
        $this->locator         = $locator;
        $this->validator       = $validator;
        $this->inserter        = $inserter;
        $this->emailSubscriber = $emailSubscriber;
        $this->currentUser     = wp_get_current_user();
    }

    /**
     * Clears and resets the importer.
     *
     * @since 0.1.0
     *
     * @return ImporterInterface
     */
    public function clear()
    {
        $this->numberTickets     = 0;
        $this->numberReplies     = 0;
        $this->numberAttachments = 0;

        return $this;
    }

    /**
     * Imports the supplied tickets into the database.
     *
     * @since 0.1.0
     *
     * @param array $tickets
     *
     * @return boolean
     */
    public function import(array $tickets)
    {
        $this->emailSubscriber->disable();
        return $this->importTickets($tickets);
    }

    /**
     * Imports the supplied tickets into the database.
     *
     * @since 0.1.0
     *
     * @param array $tickets
     *
     * @return boolean
     */
    protected function importTickets(array $tickets)
    {
        foreach ($tickets as $ticket) {
            $ticketId = $this->processTicket($ticket);

            if ($this->validator->isValidTicketId($ticketId)) {
                $this->importTicket($ticket, $ticketId);
            }
        }

        return $this->getStats(count($tickets));
    }

    /**
     * Import the Ticket.
     *
     * @since 0.1.0
     *
     * @param Ticket $ticket
     * @param int $ticketId
     *
     * @return void
     * @throws ImporterException
     */
    protected function importTicket(Ticket $ticket, $ticketId)
    {
        if (!$this->locator->findSource($ticket->getSource())) {
            $this->inserter->insertSource($ticket->getSource());
        }

        $this->processReplies($ticket, $ticketId);
        $this->processHistory($ticket, $ticketId);
    }

    /**
     * Process the ticket.  If this ticket already exists in the database,
     * skip inserting/updating and return the ticket ID.  Else, insert the
     * ticket into the database.
     *
     * @since 0.2.0
     *
     * @param Ticket $ticket
     *
     * @return bool|int|WP_Error
     * @throws ImporterException
     */
    protected function processTicket(Ticket $ticket)
    {
        // Look it up in the database. If it exists, return the ticket's post ID.
        $ticketId = $this->locator->findTicketByHelpDeskId($ticket->getHelpDeskId());
        if ($this->validator->isValidTicketId($ticketId)) {
            return $ticketId;
        }

        // Okay, we need to insert this new ticket.

        $customer = $this->processUser($ticket->getCustomer());
        // Psst...tickets don't always have an agent assigned.
        $agent = $ticket->getAgent() instanceof User
            ? $this->processUser($ticket->getAgent())
            : null;

        $ticketId = $this->inserter->insertTicket(
            $customer->ID,
            is_null($agent) ? 0 : $agent->ID,
            $ticket->getSubject(),
            $ticket->getDescription(),
            $ticket->getSource()
        );

        $this->inserter->setHelpDeskTicketId($ticketId, $ticket->getHelpDeskId());

        $this->numberTickets++;
        $this->processAttachments($ticket->getAttachments(), $ticketId);
        return $ticketId;
    }

    /**
     * Process the replies on this ticket.
     *
     * @since 0.1.0
     *
     * @param Ticket $ticket
     * @param int $ticketId
     *
     * @return void
     */
    protected function processReplies(Ticket $ticket, $ticketId)
    {
        if (empty($ticket->getReplies())) {
            return;
        }

        foreach ((array)$ticket->getReplies() as $helpDeskId => $reply) {
            if (empty($reply)) {
                continue;
            }

            // If it exists in the db, no need to import.
            $replyId = $this->locator->findReplyByHelpDeskId($helpDeskId);
            if ($this->validator->isValidReplyId($replyId)) {
                continue;
            }

            $this->processReply($reply, $ticketId, $helpDeskId);
        }
    }

    /**
     * Process this reply.
     *
     * @since 0.2.0
     *
     * @param array $reply
     * @param int $ticketId
     * @param string|int $helpDeskReplyId
     *
     * @return void
     */
    protected function processReply(array $reply, $ticketId, $helpDeskReplyId)
    {
        $author = $this->processUser($reply['user']);

		$replyId = $this->inserter->insertReply(
            $ticketId,
            $reply['reply'],
            $author,
            $reply['date'],
            $reply['read']
        );

        $this->inserter->setHelpDeskReplyId($replyId, $helpDeskReplyId);

        // Whoops, not valid. Skip it.
        if (!$this->validator->isValidReplyId($replyId)) {
            return;
        }

        $this->numberReplies++;

        if (isset($reply['attachments'])) {
            $this->processAttachments($reply['attachments'], $replyId);
        }
    }

    /**
     * Process the historical items.
     *
     * @since 0.1.0
     *
     * @param Ticket $ticket
     * @param int $ticketId
     *
     * @return void
     */
    protected function processHistory(Ticket $ticket, $ticketId)
    {
        if (null === $ticket->getHistory()) {
            return;
        }
        foreach ($ticket->getHistory() as $history) {
            // If it exists in the db, no need to import.
            if (!empty($history['id'])) { 
                $historyId = $this->locator->findHistoryByHelpDeskId($history['id']);
                if ($this->validator->isValidHistoryId($historyId)) {
                    continue;
                }
            }

            $author = $this->processUser($history['user']);

            $this->inserter->insertHistoryItem($ticketId, $author, $history['date'], $history['value']);
            if (!empty($history['id'])) {
                $this->inserter->setHelpDeskHistoryId($ticketId, $history['id']);
            }
        }
    }

    /**
     * Process the User.
     *
     * @since 0.1.0
     *
     * @param User|null $userEntity
     *
     * @return false|WP_User
     */
    protected function processUser($userEntity)
    {
        if (!$userEntity instanceof User || empty($userEntity->getEmail())) {
            return $this->currentUser;
        }

        $user = $this->locator->findUser($userEntity);
        if (!$user instanceof WP_User) {
            $user = $this->inserter->insertUser($userEntity);
        }

        if ($user instanceof WP_User) {
            return $user;
        }
    }

    /**
     * Process the attachments.
     *
     * @since 0.1.0
     *
     * @param array|null $attachments
     * @param int $ticketId
     *
     * @return void
     */
    protected function processAttachments($attachments, $ticketId)
    {
        if (empty($attachments)) {
            return;
        }

        foreach ((array)$attachments as $attachment) {
            // No attachment. Skip it.
            if (!$attachment) {
                continue;
            }

            // This one already exists. Skip it.
            if (true === $this->locator->findAttachment($ticketId, $attachment)) {
                continue;
            }

            $this->inserter->insertAttachment($ticketId, $attachment);
        }
    }

    /************************
     * Stats
     ***********************/

    /**
     * Compile the stats for this import process.
     *
     * @since 0.2.0
     *
     * @param int $ticketsReceived
     *
     * @return array
     */
    protected function getStats($ticketsReceived)
    {
        $stats = [
            'ticketsReceived' => $ticketsReceived,
            'ticketsImported' => $this->numberTickets,
            'repliesImported' => $this->numberReplies,
        ];

        $message = '';
        if (!$stats['ticketsReceived']) {
            // @codingStandardsIgnoreStart
            $message = __(
                'All done. No tickets were imported, as the Help Desk indicated there are no tickets available for your request.',
                'awesome-support-importer'
            );
            // @codingStandardsIgnoreEnd
        }

        if (!$message && !$stats['ticketsImported'] && !$stats['repliesImported']) {
            $message = __(
                'All done. No additional tickets were imported, as all these tickets were already in the database.',
                'awesome-support-importer'
            );
        }

        if (!$message) {
            $message = __('All done with the importing.', 'awesome-support-importer');
        }

        $this->notifier->log(
            $message,
            array_merge($stats, ['method' => __METHOD__])
        );

        $stats['message'] = $message;

        return $stats;
    }
}
