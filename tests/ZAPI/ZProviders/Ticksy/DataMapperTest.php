<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\ZProviders\Ticksy;

use Mockery;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\AttachmentMapper;
use Pressware\AwesomeSupport\API\Provider\Ticksy\DataMapper;
use Pressware\AwesomeSupport\API\Repository\HistoryRepository;
use Pressware\AwesomeSupport\API\Repository\ReplyRepository;
use Pressware\AwesomeSupport\API\Repository\TicketRepository;
use Pressware\AwesomeSupport\API\Repository\UserRepository;
use Pressware\AwesomeSupport\Constant\UserRoles;
use Pressware\AwesomeSupport\Entity\Ticket;
use Pressware\AwesomeSupport\Entity\User;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataMapperTest extends TestCase
{
    protected $dates;
    protected $ticketRepository;
    protected $userRepository;
    protected $historyRepository;
    protected $replyRepository;
    protected $notifier;

    public function setUp()
    {
        parent::setUp();
        $this->notifier = Mockery::mock('Pressware\AwesomeSupport\Notifications\Notifier');

        if (!$this->dates) {
            $this->dates = [
                'startDate' => '2017-01-01 00:00:00',
                'endDate'   => '',
            ];
        }

        $this->ticketRepository  = new TicketRepository($this->notifier);
        $this->historyRepository = new HistoryRepository($this->notifier);
        $this->replyRepository   = new ReplyRepository($this->notifier);
        $this->userRepository    = new UserRepository($this->notifier);
    }

    protected function createDataMapper()
    {
        $dataMapper = new DataMapper(
            $this->ticketRepository,
            $this->historyRepository,
            $this->replyRepository,
            $this->userRepository,
            new AttachmentMapper(
                [
                    'invalidAttachment' => 'The following attachment has an invalid URL and was not been imported: ',
                ],
                $this->notifier
            )
        );
        $dataMapper->init($this->dates, 'Ticksy and PHPUnit');
        return $dataMapper;
    }

    public function testConstruct()
    {
        $dataMapper = $this->createDataMapper();
        $this->assertInstanceOf(DataMapper::class, $dataMapper);
    }

    public function testFromJson()
    {
        $dataMapper = $this->createDataMapper();
        $json       = $this->getJson('ticket.json');
        $this->assertInstanceOf('stdClass', $dataMapper->fromJSON($json));
    }

    public function testToArray()
    {
        $dataMapper = $this->createDataMapper();
        $this->assertSame(['Hi there'], $dataMapper->toArray('Hi there'));

        $json = $dataMapper->fromJSON($this->getJson('ticket.json'));
        $this->assertTrue(is_array($dataMapper->toArray($json)));
    }

    public function testSkipsForStartDate()
    {
        // "time_stamp": "2017-09-18 10:35:46",

        // Test that it skips this ticket as the date is after the expected endDate.
        $this->dates['startDate'] = '2017-10-30';
        $dataMapper               = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $this->assertEmpty($this->ticketRepository->getAll());

        // Test that it skips this ticket as the ending timestamp is 1 minute after the ticket
        $this->dates['startDate'] = '2017-09-18 10:35:47';
        $dataMapper               = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $this->assertEmpty($this->ticketRepository->getAll());
    }

    public function testSkipsForEndDate()
    {
        // "time_stamp": "2017-09-18 10:35:46",

        // Test that it skips this ticket as the date is after the expected endDate.
        $this->dates['endDate'] = '2017-09-01';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $this->assertEmpty($this->ticketRepository->getAll());

        // Test that it skips this ticket as the ending timestamp is 1 minute before the ticket
        $this->dates['endDate'] = '2017-09-18 10:35:45';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $this->assertEmpty($this->ticketRepository->getAll());
    }

    public function testWithinEndDate()
    {
        // "time_stamp": "2017-09-18 10:35:46",

        // Test that it includes the ticket (doesn't skip it).
        $this->dates['endDate'] = '2017-09-18 11:35:46';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
    }

    public function testWithNoDates()
    {
        // "time_stamp": "2017-09-18 10:35:46",

        $this->dates['startDate'] = '';
        $this->dates['endDate']   = '';
        $dataMapper               = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
    }

    public function testUserMapping()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');

        $expected = 2;
        $this->assertCount($expected, $this->userRepository->getAll());

        $user = $this->userRepository->get('758928');
        $this->assertInstanceOf('Pressware\AwesomeSupport\Entity\User', $user);
        $this->assertSame('Willie', $user->getFirstName());
        $this->assertSame('Baker', $user->getLastName());
        $this->assertSame('williebaker@gmail.com', $user->getEmail());
        $this->assertSame(UserRoles::AGENT, $user->getRole());

        $user = $this->userRepository->get('759090');
        $this->assertInstanceOf('Pressware\AwesomeSupport\Entity\User', $user);
        $this->assertSame('Sally', $user->getFirstName());
        $this->assertSame('Smith', $user->getLastName());
        $this->assertSame('sally.smith@example.com', $user->getEmail());
        $this->assertSame(UserRoles::CUSTOMER, $user->getRole());
    }

    public function testTicketMapping()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
        $this->assertTrue($this->ticketRepository->has('1306046'));
        $this->assertTrue($this->ticketRepository->has(1306046));

        $expected = [
            'ticketId'    => 1306046,
            'agentID'     => 758928,
            'customerID'  => 759090,
            'subject'     => 'Multiple Replies with a Reply Attachment',
            'description' => 'This is a support request.',
            'attachments' => [
                [
                    'url'      => 'https://ticksy_attachments.s3.amazonaws.com/ticksy401Postman_784.jpg',
                    'filename' => 'ticksy401Postman.jpg',
                ],
            ],
            'createdAt'   => '2017-09-18 10:35:46',
            'updatedAt'   => '2017-10-31 15:17:05',
        ];
        $this->assertSame($expected, $this->ticketRepository->get(1306046));
    }

    public function testReplies()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $expected = 1;
        $this->assertEquals($expected, $this->replyRepository->count());
        $this->assertTrue($this->replyRepository->has('1306046'));
        $this->assertTrue($this->replyRepository->has(1306046));

        $expected = 4;
        $this->assertCount($expected, $this->replyRepository->get(1306046));


        $this->assertArrayHasKey('6565705', $this->replyRepository->get(1306046));

        $this->assertArrayHasKey('userId', $this->replyRepository->get("1306046.6565705"));
        $this->assertArrayHasKey('reply', $this->replyRepository->get("1306046.6565705"));
        $this->assertArrayHasKey('timestamp', $this->replyRepository->get("1306046.6565705"));

        $expected = [
            'ticketId'    => 1306046,
            'userId'      => '758928',
            'reply'       => '<p>Reply with an attachment is coming your way.</p>',
            'timestamp'   => '2017-10-21 17:47:51',
            'read'        => false,
            'attachments' => [
                [
                    'url'      => 'https://ticksy_attachments.s3.amazonaws.com/basketball_692.jpg',
                    'filename' => 'basketball.jpg',
                ],
            ],
        ];
        $this->assertSame($expected, $this->replyRepository->get("1306046.6565705"));
    }

    /**********************
     * Assemble Tests
     **********************/

    public function testAssembleBuildsATicket()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $tickets  = $dataMapper->assemble();
        $expected = 1;
        $this->assertCount($expected, $tickets);
        $this->arrayHasKey(1306046, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[1306046]);
    }

    public function testAssembledTicket()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $tickets = $dataMapper->assemble();

        $ticket = $tickets[1306046];
        $this->assertSame('Multiple Replies with a Reply Attachment', $ticket->getSubject());
        $this->assertSame(
            'This is a support request.',
            $ticket->getDescription()
        );
        $this->assertSame('Ticksy and PHPUnit', $ticket->getSource());
    }

    public function testAssembledUser()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');
        $tickets = $dataMapper->assemble();
        $ticket  = $tickets['1306046'];

        $this->assertNotNull($ticket->getAgent());
        $this->assertNotNull($ticket->getCustomer());
        $this->assertInstanceOf(User::class, $ticket->getAgent());
        $this->assertInstanceOf(User::class, $ticket->getCustomer());
        $this->assertSame('Willie', $ticket->getAgent()->getFirstName());
        $this->assertSame('williebaker@gmail.com', $ticket->getAgent()->getEmail());
        $this->assertSame('Sally', $ticket->getCustomer()->getFirstName());
        $this->assertSame('sally.smith@example.com', $ticket->getCustomer()->getEmail());
    }

    public function testAssembledAttachment()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');

        $tickets     = $dataMapper->assemble();
        $attachments = ($tickets[1306046])->getAttachments();

        $expected = 1;
        $this->assertCount($expected, $attachments);

        $this->assertSame(
            [
                [
                    'url'      => 'https://ticksy_attachments.s3.amazonaws.com/ticksy401Postman_784.jpg',
                    'filename' => 'ticksy401Postman.jpg',
                ],
            ],
            $attachments
        );
    }

    public function testAssembledReplies()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');

        $tickets = $dataMapper->assemble();
        $replies = ($tickets['1306046'])->getReplies();

        $expected = 4;
        $this->assertCount($expected, $replies);

        $expectedData = [
            '6375440' => [
                'reply'         => '<p>This is a reply msg...</p>',
                'date'          => '2017-09-18 12:23:53',
                'attachments'   => [],
                'userFirstName' => 'Sally',
                'userEmail'     => 'sally.smith@example.com',
            ],
            '6375500' => [
                'reply'         => 'This is another reply.',
                'date'          => '2017-09-18 12:31:02',
                'attachments'   => [],
                'userFirstName' => 'Sally',
                'userEmail'     => 'sally.smith@example.com',
            ],
            '6565705' => [
                'reply'         => '<p>Reply with an attachment is coming your way.</p>',
                'date'          => '2017-10-21 17:47:51',
                'attachments'   => [
                    [
                        'url'      => 'https://ticksy_attachments.s3.amazonaws.com/basketball_692.jpg',
                        'filename' => 'basketball.jpg',
                    ],
                ],
                'userFirstName' => 'Willie',
                'userEmail'     => 'williebaker@gmail.com',
            ],
            '6623702' => [
                'reply'         => '<p>2 more attachments showing the original Ticksy issue.</p>',
                'date'          => '2017-10-31 15:17:05',
                'attachments'   => [
                    [
                        'url'      => 'https://ticksy_attachments.s3.amazonaws.com/Zendesk-Ticksy-401_356.jpg',
                        'filename' => 'Zendesk-Ticksy-401.jpg',
                    ],
                    [
                        'url'      => 'https://ticksy_attachments.s3.amazonaws.com/ticksy401Postman_784.jpg',
                        'filename' => 'ticksy401Postman.jpg',
                    ],
                ],
                'userFirstName' => 'Willie',
                'userEmail'     => 'williebaker@gmail.com',
            ],
        ];

        foreach ($replies as $replyId => $reply) {
            $this->assertSame($expectedData[$replyId]['reply'], $reply['reply']);
            $this->assertSame($expectedData[$replyId]['date'], $reply['date']);
            $this->assertSame($expectedData[$replyId]['attachments'], $reply['attachments']);
            $this->assertInstanceOf(User::class, $reply['user']);
            $this->assertSame($expectedData[$replyId]['userFirstName'], ($reply['user'])->getFirstName());
            $this->assertSame($expectedData[$replyId]['userEmail'], ($reply['user'])->getEmail());
        }
    }

    public function testAssembledHistory()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticket.json'), 'open-tickets');

        $tickets = $dataMapper->assemble();
        $history = ($tickets[1306046])->getHistory();

        $expected = 1;
        $this->assertCount($expected, $history);

        $this->assertInstanceOf(User::class, $history[0]['user']);
        $this->assertSame('Sally', ($history[0]['user'])->getFirstName());
        $this->assertSame('open', $history[0]['value']);
        $this->assertSame('2017-09-18 10:35:46', $history[0]['date']);
    }

    /********************
     * Helpers
     ********************/

    protected function getJson($jsonFile)
    {
        return file_get_contents(__DIR__ . '/test-data/' . $jsonFile, true);
    }
}
