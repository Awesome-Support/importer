<?php

namespace Pressware\AwesomeSupport\API\Abstracts;

use DateTime;
use Pressware\AwesomeSupport\API\Contracts\AttachmentMapperInterface;
use Pressware\AwesomeSupport\Constant\Status;
use Pressware\AwesomeSupport\Traits\CastToTrait;
use Pressware\AwesomeSupport\API\Contracts\DataMapperInterface;
use Pressware\AwesomeSupport\API\Contracts\RepositoryInterface;
use Pressware\AwesomeSupport\Entity\Ticket;

abstract class DataMapper implements DataMapperInterface
{
    use CastToTrait;

    /**
     * @var RepositoryInterface
     */
    protected $ticketRepository;

    /**
     * @var RepositoryInterface
     */
    protected $historyRepository;

    /**
     * @var RepositoryInterface
     */
    protected $replyRepository;

    /**
     * @var RepositoryInterface
     */
    protected $userRepository;

    /**
     * @var AttachmentMapperInterface
     */
    protected $attachmentMapper;

    /**
     * @var array
     */
    protected $jsonResponses;

    /**
     * @var string
     */
    protected $sourceName;

    /**
     * @var int
     */
    protected $startDate;

    /**
     * @var int
     */
    protected $endDate;

    /**
     * DataMapper constructor.
     *
     * @since 0.1.0
     *
     * @param RepositoryInterface $ticketRepository
     * @param RepositoryInterface $historyRepository
     * @param RepositoryInterface $replyRepository
     * @param RepositoryInterface $userRepository
     * @param AttachmentMapperInterface $attachmentMapper
     */
    public function __construct(
        RepositoryInterface $ticketRepository,
        RepositoryInterface $historyRepository,
        RepositoryInterface $replyRepository,
        RepositoryInterface $userRepository,
        AttachmentMapperInterface $attachmentMapper
    ) {
        $this->ticketRepository  = $ticketRepository;
        $this->historyRepository = $historyRepository;
        $this->replyRepository   = $replyRepository;
        $this->userRepository    = $userRepository;
        $this->attachmentMapper  = $attachmentMapper;
    }

    /**
     * Initialize to prepare for running.
     *
     * @since 0.1.0
     *
     * @param array $dateRange
     * @param $sourceName
     *
     * @return void
     */
    public function init(array $dateRange, $sourceName)
    {
        $this->sourceName = $sourceName;
        $this->setDates($dateRange);
    }

    /**
     * Clear all of the repositories.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function clearAllRepositories()
    {
        $this->ticketRepository->clear();
        $this->historyRepository->clear();
        $this->replyRepository->clear();
        $this->userRepository->clear();
    }

    /**
     * Assembles the individual datasets into the final Ticket model.
     *
     * @since 0.2.0
     *
     * @return array Returns an array of Ticket models
     */
    public function assemble()
    {
        $tickets = [];
        foreach ($this->ticketRepository->getAll() as $ticketId => $ticket) {
            $tickets[$ticketId] = new Ticket(
                $ticketId,
                isset($ticket['agentID']) ? $this->userRepository->get($ticket['agentID']) : null,
                isset($ticket['customerID']) ? $this->userRepository->get($ticket['customerID']) : null,
                $this->sourceName,
                $ticket['subject'],
                $ticket['description'],
                $ticket['createdAt'],
                $ticket['updatedAt'],
                $ticket['attachments'],
                $this->assembleReplies($ticketId),
                $this->assembleHistory($ticketId)
            );
        }
        return $tickets;
    }

    /**
     * Assembly the replies for the ticket model.
     *
     * @since 0.2.0
     *
     * @param int|string $ticketId
     *
     * @return array|null
     */
    protected function assembleReplies($ticketId)
    {
        $ticketReplies = $this->replyRepository->get($ticketId);
        if (!$ticketReplies) {
            return null;
        }
        $replies = [];
        foreach ((array)$ticketReplies as $helpDeskId => $reply) {
            $replies[$helpDeskId] = [
                'user'        => $this->userRepository->get($reply['userId']),
                'reply'       => $reply['reply'],
                'date'        => $reply['timestamp'],
                'read'        => $reply['read'],
                'attachments' => $reply['attachments'],
                'private'     => (isset($reply['private'])) ? $reply['private'] : false
            ];
        }
        return $replies;
    }

    /**
     * Assembly the history items for the ticket model.
     *
     * @since 0.1.0
     *
     * @param int|string $ticketId
     *
     * @return array|null
     */
    protected function assembleHistory($ticketId)
    {
        $allHistory = $this->historyRepository->get($ticketId);
        if (!$allHistory) {
            return null;
        }
        $history = [];
        foreach ((array)$allHistory as $item) {
            $item['user'] = $this->userRepository->get($item['user']);
            $history[]    = $item;
        }
        return $history;
    }

    /**
     * Maps the incoming JSON to the individual repositories.
     *
     * @since 0.1.0
     *
     * @param string $json
     * @param string $key (Optional)
     *
     * @return void
     */
    abstract public function mapJSON($json, $key = '');

    /**
     * Get all of the repositories.
     *
     * @since 0.1.0
     *
     * @return array Returns an array of users, tickets, replies, and history.
     */
    public function getAll()
    {
        return [
            'users'   => $this->userRepository->getAll(),
            'tickets' => $this->ticketRepository->getAll(),
            'replies' => $this->replyRepository->getAll(),
            'history' => $this->historyRepository->getAll(),
        ];
    }

    /*******************************************
     * Mappers
     ******************************************/

    /**
     * Build an array of Attachment Data Structures.
     *
     * @since 0.1.0
     *
     * @param array|mixed $attachments
     * @param int $ticketId
     * @param int $replyId
     *
     * @return bool
     */
    protected function mapAttachments($attachments, $ticketId, $replyId = 0)
    {
        return $this->attachmentMapper->map(
            $attachments,
            $ticketId,
            $replyId ? $this->replyRepository : $this->ticketRepository,
            [$this, 'mapAttachment'],
            $replyId
        );
    }

    /**
     * Map the attachment and convert into an array data structure.
     *
     * Override is method in your child class for the specific url and filename points.
     *
     * @since 0.1.0
     *
     * @param \stdClass|mixed $attachment
     *
     * @return array
     * return [
     *      'url' => 'holds a valid URL',
     *      'filename' => 'holds the filename, e.g. image.jpg',
     * ]
     */
    abstract public function mapAttachment($attachment);

    /*******************************************
     * Helpers
     ******************************************/

    /**
     * Checks if the timestamp is within the date range.
     *
     * @since 0.1.0
     *
     * @param DateTime|string $timestamp
     * @param bool|null $shouldCheckStartDate (Optional) Set to true to check the start date/time
     *
     * @return bool
     */
    protected function withinDateRange($timestamp, $shouldCheckStartDate = null)
    {
        if (!$this->startDate && !$this->endDate) {
            return true;
        }

        $timestamp = strtotime($timestamp);

        // When enabled, check the start date/time.
        if (true === $shouldCheckStartDate) {
            // Item's timestamp is before the start date/time.
            if ($this->startDate && $timestamp < $this->startDate) {
                return false;
            }
        }

        // No end date limitation.
        if (!$this->endDate) {
            return true;
        }

        return $timestamp <= $this->endDate;
    }

    /**
     * Initialize the dates query.
     *
     * @since 0.1.0
     *
     * @param array $dates
     *
     * @return void
     */
    protected function setDates(array $dates)
    {
        foreach ($dates as $property => $date) {
            if (!$date) {
                continue;
            }

            $this->{$property} = strtotime($date);
        }
    }

    protected function storeUser($userId, $userName, $userEmail, $role)
    {
        $user = $this->userRepository->create($userId, $userName, $userEmail, $role);
        if ($user) {
            $this->userRepository->createModel($user);
        }
    }

    /**
     * Get the history's status for this ticket.
     *
     * @since 0.1.0
     *
     * @param string $eventStatus
     *
     * @return string|void
     */
    protected function getHistoryStatus($eventStatus)
    {
        switch ($eventStatus) {
            case 'closed':
            case 'solved':
            case 'deleted':
                return Status::CLOSED;
            case 'hold':
                return Status::HOLD;
            case 'open':
            case 'active':
                return Status::OPEN;
            case 'pending':
                return Status::PROCESSING;
        }
    }
}
