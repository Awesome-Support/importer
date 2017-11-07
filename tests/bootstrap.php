<?php

namespace Pressware\AwesomeSupport\Tests;

// @codingStandardsIgnoreFile

date_default_timezone_set('UTC');

define('TESTS_CONFIG_DIR', __DIR__ . '/config/');
define('RUN_HELP_DESK_ENDPOINT_TESTS', false);

// These rate limit tests are very, very, very long running.
define('RUN_TICKSY_RATE_LIMIT_TEST', false);
define('RUN_ZENDESK_RATE_LIMIT_TEST', false);

require __DIR__ . '/../vendor/autoload.php';
