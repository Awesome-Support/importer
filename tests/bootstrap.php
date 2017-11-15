<?php

namespace Pressware\AwesomeSupport\Tests;

// @codingStandardsIgnoreFile

date_default_timezone_set('UTC');

/**
 * STOP: Before you turn on the endpoint or rate limiter tests, make sure you've copied the config files into the
 * '/private/` folder and then configured each.
 */
define('HELPSCOUT_CONFIG_FILE', __DIR__ . '/config/private/help-scout.php');
define('TICKSY_CONFIG_FILE', __DIR__ . '/config/private/ticksy.php');
define('ZENDESK_CONFIG_FILE', __DIR__ . '/config/private/zendesk.php');

define('RUN_HELP_DESK_ENDPOINT_TESTS', false);

// These rate limit tests are very, very, VERY long running. Seriously, go get a coffee.
define('RUN_TICKSY_RATE_LIMIT_TEST', false);
define('RUN_ZENDESK_RATE_LIMIT_TEST', false);

require __DIR__ . '/../vendor/autoload.php';
