<?php

namespace Pressware\AwesomeSupport\Importer;

use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

class ServiceProvider
{
    /**
     * Create an instance of the Importer and each of its dependencies.
     *
     * @since 0.1.2
     *
     * @param NotificationInterface $notifier
     *
     * @return Importer
     */
    public function create(NotificationInterface $notifier)
    {
        $locator = new Locator();
        $validator = new Validator($notifier);

        return new Importer(
            $notifier,
            $locator,
            $validator,
            new Inserter($notifier, $locator, $validator),
            new EmailSubscriber()
        );
    }
}
