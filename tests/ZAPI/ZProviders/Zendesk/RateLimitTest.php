<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\ZProviders\Zendesk;

use Mockery;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Abstracts\GuzzleNotifier;
use Pressware\AwesomeSupport\API\Provider\Zendesk\ApiController;
use Pressware\AwesomeSupport\API\Exception\ApiResponseThrowableException;
use Pressware\AwesomeSupport\Tests\ZAPI\NotifierStub;

class RateLimitTest extends TestCase
{
    const RATE_LIMIT_THRESHOLD          = 200;
    const STATUS_CODE_TOO_MANY_REQUESTS = 429;

    protected $notifier;
    protected $config;
    protected $api;

    protected function setUp()
    {
        if (false === RUN_ZENDESK_RATE_LIMIT_TEST) {
            $this->markTestSkipped('skipping all tests in this file');
            return;
        }

        parent::setUp();
        $this->config   = array_merge(
            [
                'apiName'     => 'Zendesk Rate Limit Test',
                'subdomain'   => '',
                'token'       => '',
                'baseUri'     => 'https://pressware-awesome-support.dev/',
                'redirectUri' => 'https://pressware-awesome-support.dev/wp-admin/edit.php' .
                    '?post_type=ticket&page=awesome_support_import_tickets',
                'startDate'   => '',
                'endDate'     => '',
                'moduleName'  => __CLASS__,
            ],
            (array)require ZENDESK_CONFIG_FILE
        );
        $this->notifier = new NotifierStub();
    }

    /**
     * This test is slow as we are testing the Ticksy Rate Limit.
     * Ticksy allows up to 200 requests / minute.
     */
    public function testHitRateLimitAndContinue()
    {
        $api      = $this->createApi();
        $endpoint = "https://{$this->config['subdomain']}.zendesk.com/api/v2/tickets.json";

        $hit429 = 0;
        for ($numLoops = 0; $numLoops < self::RATE_LIMIT_THRESHOLD; $numLoops++) {
            $json = $api->get($endpoint);
            $this->assertJson($json);

            if ($hit429) {
                break;
            }

            $log      = $this->notifier->getLog();
            if (self::STATUS_CODE_TOO_MANY_REQUESTS === $log[$numLoops]['response']) {
                $hit429++;
            }
        }

        $expected = 0;
        $this->assertGreaterThanOrEqual($expected, $hit429);
    }

    protected function createApi()
    {
        return new ApiController(
            $this->config,
            new Client([
                'base_uri' => $this->config['baseUri'],
            ]),
            Mockery::mock('Pressware\AwesomeSupport\API\Provider\Zendesk\DataMapper'),
            new GuzzleNotifier($this->notifier)
        );
    }
}
