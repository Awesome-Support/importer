<?php

namespace Pressware\AwesomeSupport\Importer;

use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

class Validator
{
    const MIN_VALID_INTEGER = 1;

    /**
     * @var NotificationInterface
     */
    protected $notifier;

    public function __construct(NotificationInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    public function isValidTicketId($ticketId)
    {
        return $this->validNonZeroInteger($ticketId);
    }

    public function isValidReplyId($replyId)
    {
        return $this->validNonZeroInteger($replyId);
    }

    public function isValidHistoryId($replyId)
    {
        return $this->validNonZeroInteger($replyId);
    }

    protected function validNonZeroInteger($integer)
    {
        return is_integer($integer) &&
            (int)$integer >= self::MIN_VALID_INTEGER;
    }
}
