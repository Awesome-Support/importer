<?php

namespace Pressware\AwesomeSupport\Entity;

class Ticket
{
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
     * @param User|null $agent New tickets may not have an agent assigned to them.
     * @param User|null $customer
     * @param $source
     * @param $subject
     * @param $description
     * @param $attachments
     * @param $replies
     * @param $history
     */
    public function __construct(
        $agent,
        $customer,
        $source,
        $subject,
        $description,
        array $attachments = null,
        array $replies = null,
        array $history = null
    ) {
        $this->agent       = $agent;
        $this->customer    = $customer;
        $this->source      = $source;
        $this->subject     = $subject;
        $this->description = $description;
        $this->attachments = $attachments;
        $this->replies     = $replies;
        $this->history     = $history;
        // TODO: Tags, Custom Fields, Multiple Agents
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
