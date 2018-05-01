<?php

namespace Pressware\AwesomeSupport\Entity;

class Ticket
{
    /**
     * The original ID from the Help Desk SaaS provider.
     *
     * @since 0.2.0
     *
     * @var string|int
     */
    private $helpDeskId;

    /**
     * @var User
     */
    private $agent;

    /**
     * @var User
     */
    private $customer;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $createdAt;

    /**
     * @var string
     */
    private $updatedAt;

    /**
     * @var array|null
     */
    private $attachments;

    /**
     * @var array|null
     */
    private $replies;

    /**
     * @var array|null
     */
    private $history;

    /**
     * @var string
     */
    private $slug;

    /**
     * Ticket constructor.
     *
     * @since 0.2.0
     *
     * @param string|int $helpDeskId Help Desk provider's ticket ID.
     * @param User|null $agent New tickets may not have an agent assigned to them.
     * @param User|null $customer
     * @param $source
     * @param $subject
     * @param $description
     * @param $createdAt
     * @param $updatedAt
     * @param $attachments
     * @param $replies
     * @param $history
     */
    public function __construct(
        $helpDeskId,
        $agent,
        $customer,
        $source,
        $subject,
        $description,
        $createdAt,
        $updatedAt,
        array $attachments = null,
        array $replies = null,
        array $history = null
    ) {
        $this->helpDeskId  = $helpDeskId;
        $this->agent       = $agent;
        $this->customer    = $customer;
        $this->source      = $source;
        $this->subject     = $subject;
        $this->description = $description;
        $this->createdAt   = $createdAt;
        $this->updatedAt   = $updatedAt;
        $this->attachments = $attachments;
        $this->replies     = $replies;
        $this->history     = $history;
        // TODO: Tags, Custom Fields, Multiple Agents
    }

    /**
     * Get the Help Desk provider's original ticket ID.
     *
     * @since 0.2.0
     *
     * @return string|int
     */
    public function getHelpDeskId()
    {
        return $this->helpDeskId;
    }

    /**
     * @return User
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * @return User
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        if (!$this->slug) {
            $this->slug = sanitize_title($this->subject);
        }
        return $this->slug;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return null
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @return null
     */
    public function getReplies()
    {
        return $this->replies;
    }

    /**
     * @return null
     */
    public function getHistory()
    {
        return $this->history;
    }
}
