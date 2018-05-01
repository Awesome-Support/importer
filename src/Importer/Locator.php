<?php

namespace Pressware\AwesomeSupport\Importer;

use Pressware\AwesomeSupport\Entity\Ticket;
use Pressware\AwesomeSupport\Entity\User;
use WP_Post;

class Locator
{
    const NO_RECORDS_FOUND = 0;
    const NO_MATCH_FOUND   = -1;

    /**
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * @var array
     */
    protected $existingAttachments;

    public function __construct(array $existingAttachments = [])
    {
        global $wpdb;
        $this->wpdb                = $wpdb;
        $this->existingAttachments = $existingAttachments;
    }

    /**
     * @param User $user
     *
     * @return false|WP_User
     */
    public function findUser(User $user)
    {
        return get_user_by('email', $user->getEmail());
    }

    /**
     * @param $source
     *
     * @return mixed
     */
    public function findSource($source)
    {
        if (!$source) {
            return false;
        }
        return term_exists($source, 'ticket_channel');
    }

    /**
     * Checks if the ticket already exists in the database. If yes, the ticket
     * ID is returned; else false.
     *
     * @since 0.1.0
     *
     * @param Ticket $ticket
     *
     * @return bool|int
     */
    public function findTicket(Ticket $ticket)
    {
        $post = get_page_by_path($ticket->getSlug(), OBJECT, 'ticket');

        return $post instanceof WP_Post
            ? $post->ID
            : self::NO_RECORDS_FOUND;
    }

    /**
     * Checks if the ticket already exists in the database by help desk ID. If yes, the ticket
     * post ID is returned; else false.
     *
     * @since 0.1.0
     *
     * @param HelpDeskId $helpDeskId
     *
     * @return bool|int
     */
    public function findTicketByHelpDeskId($helpDeskId)
    {
        $sqlQuery = $this->wpdb->prepare(
            'SELECT * ' .
            "FROM {$this->wpdb->postmeta} " .
            "WHERE meta_key='_wpas_help_desk_ticket_id' AND meta_value = %s",
            $helpDeskId
        );

        $records = $this->wpdb->get_results($sqlQuery);
        if ($records) {
            foreach ((array)$records as $record) {
                return (int)$record->post_id;
            }
        }

        return self::NO_RECORDS_FOUND;
    }

    /**
     * Search the database for this reply to avoid duplicates during import.
     *
     * @since 0.1.0
     *
     * @param int $ticketId
     * @param string $reply
     *
     * @return int
     */
    public function findReply($ticketId, $reply)
    {
        // Request the replies from the database.
        $records = $this->getRepliesFromDatabase("reply-to-ticket-{$ticketId}");
        if (empty($records)) {
            return self::NO_RECORDS_FOUND;
        }

        foreach ((array)$records as $record) {
            if ($record->reply === $reply) {
                return (int)$record->replyId;
            }
        }

        return self::NO_MATCH_FOUND;
    }

    /**
     * Checks if the reply already exists in the database by help desk ID. If yes, the reply
     * post ID is returned; else false.
     *
     * @since 0.1.0
     *
     * @param HelpDeskId $helpDeskId
     *
     * @return bool|int
     */
    public function findReplyByHelpDeskId($helpDeskId)
    {
        $sqlQuery = $this->wpdb->prepare(
            'SELECT * ' . 
            "FROM {$this->wpdb->postmeta} " . 
            "WHERE meta_key='_wpas_help_desk_reply_id' AND meta_value = %s",
            $helpDeskId
        );

        $records = $this->wpdb->get_results($sqlQuery);
        if ($records) {
            foreach ((array)$records as $record) {
                return (int)$record->post_id;
            }
        }

        return self::NO_RECORDS_FOUND;
    }

    /**
     * Checks if the history already exists in the database by help desk ID. If yes, the history
     * post ID is returned; else false.
     *
     * @since 0.1.0
     *
     * @param HelpDeskId $helpDeskId
     *
     * @return bool|int
     */
    public function findHistoryByHelpDeskId($helpDeskId)
    {
        $sqlQuery = $this->wpdb->prepare(
            'SELECT * ' . 
            "FROM {$this->wpdb->postmeta} " . 
            "WHERE meta_key='_wpas_help_desk_history_id' AND meta_value = %s",
            $helpDeskId
        );

        $records = $this->wpdb->get_results($sqlQuery);
        if ($records) {
            foreach ((array)$records as $record) {
                return (int)$record->post_id;
            }
        }

        return self::NO_RECORDS_FOUND;
    }

    /**
     * Finds a post id by meta id
     *
     * @since 0.1.0
     *
     * @param MetaId $metaId
     *
     * @return bool|int
     */
    public function findPostByMetaId($metaId)
    {
        $sqlQuery = $this->wpdb->prepare(
            'SELECT * ' . 
            "FROM {$this->wpdb->postmeta} " . 
            "WHERE meta_id=%d",
            (int)$metaId
        );

        $records = $this->wpdb->get_results($sqlQuery);
        if ($records) {
            foreach ((array)$records as $record) {
                return (int)$record->post_id;
            }
        }

        return self::NO_RECORDS_FOUND;
    }

    /**
     * Checks the database for this attachment file.
     *
     * @since 0.1.0
     *
     * @param int $ticketId
     * @param array $attachment
     *
     * @return bool
     */
    public function findAttachment($ticketId, array $attachment)
    {
        $this->getExistingAttachments($ticketId);
        if (!$this->hasExistingAttachments($ticketId)) {
            return false;
        }

        foreach ($this->existingAttachments[$ticketId] as $cachedAttachment) {
            if (str_ends_with($cachedAttachment->attachmentUrl, $attachment['filename'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all of the existing attachments for this ticket from the database.
     *
     * @since 0.1.0
     *
     * @param int $ticketId
     *
     * @return null
     */
    protected function getExistingAttachments($ticketId)
    {
        if ($this->hasExistingAttachments($ticketId)) {
            return;
        }

        $sqlQuery = $this->wpdb->prepare(
            'SELECT p.ID, p.post_parent as ticketId, p.guid as attachmentUrl ' . 
            "FROM {$this->wpdb->posts} AS p " . 
            "WHERE p.post_type = 'attachment' AND p.post_parent = %s",
            $ticketId
        );

        $records = $this->wpdb->get_results($sqlQuery);
        if ($records) {
            $this->existingAttachments[$ticketId] = $records;
        }
    }

    public function hasExistingAttachments($ticketId)
    {
        return array_key_exists($ticketId, $this->existingAttachments) &&
            $this->existingAttachments[$ticketId];
    }

    /**
     * Grab all of the replies for this ticket ID.
     *
     * @since 0.1.0
     *
     * @param $slug
     *
     * @return array|null|object
     */
    protected function getRepliesFromDatabase($slug)
    {
        $sqlQuery = $this->wpdb->prepare(
            'SELECT p.ID AS replyId, p.post_content AS reply ' . 
            "FROM {$this->wpdb->posts} AS p " . 
            "WHERE p.post_type = 'ticket_reply' AND p.post_name = %s OR p.post_name LIKE %s",
            $slug,
            $slug . '-%'
        );

        return $this->wpdb->get_results($sqlQuery);
    }
}
