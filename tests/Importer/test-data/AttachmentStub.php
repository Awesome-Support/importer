<?php

namespace Pressware\AwesomeSupport\Tests\Importer;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class AttachmentStub
{
    public $ID;
    public $ticketId;
    public $attachmentUrl;

    public function __construct(...$args)
    {
        $this->ID            = $args[0];
        $this->ticketId      = $args[1];
        $this->attachmentUrl = $args[2];
    }
}
