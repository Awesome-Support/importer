<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\Abstracts;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Abstracts\GuzzleNotifier;
use Pressware\AwesomeSupport\API\Exception\ApiResponseThrowableException;
use Pressware\AwesomeSupport\API\Exception\ApiUnauthorizedException;
use Pressware\AwesomeSupport\Tests\ZAPI\HttpStub;
use Pressware\AwesomeSupport\Tests\ZAPI\NotifierStub;

/**
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
class GuzzleClientTest extends TestCase
{
    protected $notifier;

    public function setUp()
    {
        parent::setUp();

        $this->notifier = new NotifierStub();
    }

    public function test200Status()
    {
        $httpClient = $this->createHttpClient(
            new MockHandler([
                new Response(200, ['X-Foo' => 'Bar'], 'WooHoo, it works'),
            ])
        );

        list($response, $executionTime) = $this->getHttpResponse($httpClient);
        $this->assertSame('WooHoo, it works', $response);

        $log = $this->notifier->getLog();
        $this->assertNotEmpty($log);
        $expected = 200;
        $this->assertSame($expected, $log[0]['response']);
        $this->assertSame('Success. Received packets from Endpoint Test with endpoint /.', $log[0]['message']);
        $this->assertSame(['httpCode' => 200], $log[0]['context']);

        // should happen very, very quickly.
        $expected = 0.1;
        $this->assertLessThan($expected, $executionTime);
    }

    public function testRateLimit()
    {
        $retryAfterSeconds = 2;
        $httpClient        = $this->createHttpClient(
            new MockHandler([
                new ClientException(
                    'Whoops, you hit the rate limit',
                    new Request('GET', 'test'),
                    new Response(429, ['Retry-After' => $retryAfterSeconds])
                ),
                new Response(200, ['X-Foo' => 'Bar'], 'WooHoo, it works'),
            ])
        );

        list($response, $executionTime) = $this->getHttpResponse($httpClient);

        // Check the log.
        $log = $this->notifier->getLog();
        $this->assertNotEmpty($log);
        $expected = 2;
        $this->assertCount($expected, $log);

        // 1st GET
        $expected = 429;
        $this->assertSame($expected, $log[0]['response']);
        $this->assertSame(
            "Endpoint Test hit the rate limit. Delaying for {$retryAfterSeconds} seconds. ".
            'Then request will resend.',
            $log[0]['message']
        );
        $this->assertSame(['httpCode' => 429, 'delay' => $retryAfterSeconds * 1000], $log[0]['context']);
        $this->assertGreaterThanOrEqual($retryAfterSeconds, $executionTime);

        // 2nd GET after time delay.
        $expected = 200;
        $this->assertSame($expected, $log[1]['response']);
        $this->assertSame(
            'Success. Received packets from Endpoint Test with endpoint /.',
            $log[1]['message']
        );
        $this->assertSame(['httpCode' => 200], $log[1]['context']);
    }

    public function testUnauthorized()
    {
        $this->expectException(ApiUnauthorizedException::class);
        $expected = 401;

        $httpClient = $this->createHttpClient(
            new MockHandler([
                new ClientException(
                    'You are not authorized',
                    new Request('GET', 'test'),
                    new Response($expected)
                ),
            ])
        );

        $response = $httpClient->get('/');

        $this->assertNull($response);
        $this->assertNotEmpty($this->notifier->getError());
        $this->assertSame($expected, $this->notifier->getErrorPacket('statusCode'));
        $this->assertSame('You are not authorized', $this->notifier->getErrorPacket('message'));
        $this->assertSame(
            'Pressware\AwesomeSupport\API\Exception\ApiUnauthorizedException',
            $this->notifier->getErrorPacket('errorClass')
        );
    }

    protected function getHttpResponse($httpClient)
    {
        $startTime = microtime(true);
        $response  = $httpClient->get('/');
        $endTime   = microtime(true);
        return [$response, $endTime - $startTime];
    }

    protected function createHttpClient($mock)
    {
        return new HttpStub(
            [
                'apiName'  => 'Endpoint Test',
                'username' => '',
                'token'    => '',
                'moduleName' => 'HttpStub'
            ],
            new Client([
                'handler' => $mock,
            ]),
            new GuzzleNotifier($this->notifier)
        );
    }
}
