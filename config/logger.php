<?php

namespace Pressware\AwesomeSupport\Notifications;

use Monolog\Logger as MonologLogger;

return [
    'rootPath'            => '',
    'logPath'             => '/logs/info.log',
    'errorLogPath'        => '/logs/error.log',
    'levels'              => [
        'emergency' => MonologLogger::EMERGENCY,
        'alert'     => MonologLogger::ALERT,
        'critical'  => MonologLogger::CRITICAL,
        'error'     => MonologLogger::ERROR,
        'warning'   => MonologLogger::WARNING,
        'notice'    => MonologLogger::NOTICE,
        'info'      => MonologLogger::INFO,
        'debug'     => MonologLogger::DEBUG,
    ],
];
