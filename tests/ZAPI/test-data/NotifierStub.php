<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI;

use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

class NotifierStub implements NotificationInterface
{
    protected $error;
    protected $log = [];
    const SUCCESS = 'success';

    public function getError()
    {
        return $this->error;
    }

    public function getErrorResponse()
    {
        if (array_key_exists('response', $this->error)) {
            return $this->error['response'];
        }
    }

    public function getErrorPacket($key = '')
    {
        if (!array_key_exists('errorPacket', $this->error)) {
            return;
        }
        if (!$key) {
            return $this->error['errorPacket'];
        }

        if ($key && array_key_exists($key, $this->error['errorPacket'])) {
            return $this->error['errorPacket'][$key];
        }
    }

    public function getLog()
    {
        return $this->log;
    }

    public function startListeningForErrors()
    {
        return 'listening';
    }

    public function fireErrorLogger(array $errorPacket, array $context = [])
    {
        $this->error = [
            'response'    => $context['httpCode'],
            'errorPacket' => $errorPacket,
            'context'     => $context,
        ];
    }

    public function log($message, $context = [])
    {
        $this->log[] = [
            'response' => $context['httpCode'],
            'message'  => $message,
            'context'  => $context,
        ];
    }
}
