<?php

namespace Pressware\AwesomeSupport\Importer;

use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

class ServiceProvider
{
    public function create(NotificationInterface $notifier)
    {
        $locator = new Locator();
        $validator = new Validator($notifier);

        return new Importer(
            $notifier,
            $locator,
            $validator,
            new Inserter($notifier, $locator, $validator)
        );
    }
}
