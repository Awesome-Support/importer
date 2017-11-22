<?php

namespace Pressware\AwesomeSupport\Notifications;

use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;
use Pressware\AwesomeSupport\Subscriber\AbstractSubscriber;

/**
 * Class NotifierSubscriber
 * @package Pressware\AwesomeSupport\Notifications
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class NotifierSubscriber extends AbstractSubscriber
{
    /**
     * @var string
     */
    protected $screenName;

    /**
     * @var NotificationInterface
     */
    protected $notifier;

    /**
     * MailboxSubscriber constructor.
     *
     * @since 0.1.1
     *
     * @param array $config
     * @param NotificationInterface $notifier
     */
    public function __construct(array $config, NotificationInterface $notifier)
    {
        parent::__construct($config);
        $this->notifier = $notifier;
    }

    /**
     * {@inheritdoc}
     */
    public static function getHooks()
    {
        return [
            'admin_init'                    => 'turnOnNotifier',
        ];
    }

    /**
     * Turn on the notifier WHEN the user is logged in and is on the Importer screen.
     *
     * @since 0.1.1
     *
     * @return void
     */
    public function turnOnNotifier()
    {
        if (!is_user_logged_in()) {
            return;
        }

        if ($this->isCorrectScreen()) {
            $this->notifier->startListeningForErrors();
        }
    }

    /**
     * Checks if this is the correct admin screen.
     *
     * @since 0.1.1
     *
     * @return bool
     */
    protected function isCorrectScreen()
    {
        if (!array_key_exists('page', $_GET)) {
            return false;
        }
        return ($_GET['page'] === $this->screenName);
    }
}
