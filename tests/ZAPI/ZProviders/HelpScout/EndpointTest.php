<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\ZProviders\HelpScout;

use Mockery;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Abstracts\GuzzleNotifier;
use Pressware\AwesomeSupport\API\Exception\ApiUnauthorizedException;
use Pressware\AwesomeSupport\API\Provider\HelpScout\ApiController;
use Pressware\AwesomeSupport\Tests\ZAPI\NotifierStub;
use Pressware\AwesomeSupport\API\Exception\ApiResponseThrowableException;
use function Pressware\AwesomeSupport\Tests\runHelpDeskEndpointTests;

class EndpointTest extends TestCase
{
    protected $notifier;
    protected $config;
    protected $api;

    protected function setUp()
    {
        if (false === RUN_HELP_DESK_ENDPOINT_TESTS) {
            $this->markTestSkipped('skipping all tests in this file');
            return;
        }

        parent::setUp();
        $this->config   = array_merge(
            [
                'apiName'     => 'HelpScout Endpoint Test',
                'subdomain'   => '',
                'token'       => '',
                'baseUri'     => 'https://pressware-awesome-support.dev/',
                'redirectUri' => 'https://pressware-awesome-support.dev/wp-admin/edit.php' .
                    '?post_type=ticket&page=awesome_support_import_tickets',
                'startDate'   => '',
                'endDate'     => '',
                'moduleName'  => __CLASS__,
            ],
            (array)require HELPSCOUT_CONFIG_FILE
        );
        $this->notifier = new NotifierStub();
    }

    public function testInvalidToken401Unauthorized()
    {
        $this->expectException(ApiUnauthorizedException::class);

        $this->config['token'] = 'wrongToken';
        $api                   = $this->createApi();
        $api->getMailboxes();

        $expected = 401;
        $this->assertNotEmpty($this->notifier->getError());
        $this->assertSame($expected, $this->notifier->getErrorPacket('statusCode'));
        $this->assertSame(
            'Pressware\AwesomeSupport\API\Exception\ApiUnauthorizedException',
            $this->notifier->getErrorPacket('errorClass')
        );
    }

    public function testMailboxes200()
    {
        $api       = $this->createApi();
        $mailboxes = $api->getMailboxes();

        $log = $this->notifier->getLog();
        $this->assertNotEmpty($log);
        $expected = 1;
        $this->assertCount($expected, $log);

        $expected = 200;
        $this->assertEquals($expected, $log[0]['response']);
        $this->assertSame(
            "Success. Received packets from {$this->config['apiName']} with ".
            'endpoint https://api.helpscout.net/v1/mailboxes.json.',
            $log[0]['message']
        );
        $this->assertSame(['httpCode' => 200], $log[0]['context']);

        $this->assertNotEmpty($mailboxes);
        $this->assertTrue(is_array($mailboxes));
    }

    public function test200()
    {
        $api        = $this->createApi();
        $mailboxIDs = array_keys($api->getMailboxes());
        $endpoint   = sprintf(
            'https://api.helpscout.net/v1/mailboxes/%s/conversations.json',
            array_pop($mailboxIDs)
        );
        $json       = $api->get($endpoint);

        $log = $this->notifier->getLog();
        $this->assertNotEmpty($log);
        $expected = 2;
        $this->assertCount($expected, $log);

        $expected = 200;
        $this->assertEquals($expected, $log[1]['response']);
        $this->assertSame(
            "Success. Received packets from {$this->config['apiName']} with endpoint {$endpoint}.",
            $log[1]['message']
        );
        $this->assertSame(['httpCode' => 200], $log[1]['context']);
        $this->assertJson($json);
    }

    protected function createApi()
    {
        $dataMapper = Mockery::mock('Pressware\AwesomeSupport\API\Provider\HelpScout\DataMapper');
        return new ApiController(
            $this->config,
            new Client([
                'base_uri' => $this->config['baseUri'],
            ]),
            $dataMapper,
            new GuzzleNotifier($this->notifier)
        );
    }
}
