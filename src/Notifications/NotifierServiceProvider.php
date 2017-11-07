<?php

namespace Pressware\AwesomeSupport\Notifications;

use Monolog\Logger as MonologLogger;

class NotifierServiceProvider
{
    public function create(array $config)
    {
        $errorLogger      = new Logger(
            new MonologLogger('awesome-support-importer'),
            $config
        );
        $infoLogger       = new Logger(
            new MonologLogger('awesome-support-importer'),
            $config,
            'info'
        );
        $exceptionHandler = new ExceptionHandler($config);

        return new Notifier($config, $errorLogger, $infoLogger, $exceptionHandler);
    }
}
