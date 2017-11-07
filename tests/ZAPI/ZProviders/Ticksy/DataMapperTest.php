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
        $json       = $this->getJson('ticksy.json');
        $this->assertInstanceOf('stdClass', $dataMapper->fromJSON($json));
    }

    public function testToArray()
    {
        $dataMapper = $this->createDataMapper();
        $this->assertSame(['Hi there'], $dataMapper->toArray('Hi there'));

        $json = $dataMapper->fromJSON($this->getJson('ticksy.json'));
        $this->assertTrue(is_array($dataMapper->toArray($json)));
    }

    public function testSkipsForStartDate()
    {
        // "time_stamp": "2017-09-18 10:35:46",

        // Test that it skips this ticket as the date is after the expected endDate.
        $this->dates['startDate'] = '2017-10-30';
        $dataMapper               = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $this->assertEmpty($this->ticketRepository->getAll());

        // Test that it skips this ticket as the ending timestamp is 1 minute after the ticket
        $this->dates['startDate'] = '2017-09-18 10:35:47';
        $dataMapper               = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $this->assertEmpty($this->ticketRepository->getAll());
    }

    public function testSkipsForEndDate()
    {
        // "time_stamp": "2017-09-18 10:35:46",

        // Test that it skips this ticket as the date is after the expected endDate.
        $this->dates['endDate'] = '2017-09-01';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $this->assertEmpty($this->ticketRepository->getAll());

        // Test that it skips this ticket as the ending timestamp is 1 minute before the ticket
        $this->dates['endDate'] = '2017-09-18 10:35:45';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $this->assertEmpty($this->ticketRepository->getAll());
    }

    public function testWithinEndDate()
    {
        // "time_stamp": "2017-09-18 10:35:46",

        // Test that it includes the ticket (doesn't skip it).
        $this->dates['endDate'] = '2017-09-18 11:35:46';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
    }

    public function testWithNoDates()
    {
        // "time_stamp": "2017-09-18 10:35:46",

        $this->dates['startDate'] = '';
        $this->dates['endDate']   = '';
        $dataMapper               = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
    }

    public function testUserMapping()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');

        $expected = 2;
        $this->assertCount($expected, $this->userRepository->getAll());

        $user = $this->userRepository->get('1');
        $this->assertInstanceOf('Pressware\AwesomeSupport\Entity\User', $user);
        $this->assertSame('Willie', $user->getFirstName());
        $this->assertSame('Baker', $user->getLastName());
        $this->assertSame('willie.baker@gmail.com', $user->getEmail());
        $this->assertSame(UserRoles::AGENT, $user->getRole());

        $user = $this->userRepository->get('2');
        $this->assertInstanceOf('Pressware\AwesomeSupport\Entity\User', $user);
        $this->assertSame('Sally', $user->getFirstName());
        $this->assertSame('Smith', $user->getLastName());
        $this->assertSame('sally.smith@example.com', $user->getEmail());
        $this->assertSame(UserRoles::CUSTOMER, $user->getRole());
    }

    public function testTicketMapping()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
        $this->assertTrue($this->ticketRepository->has('1'));
        $this->assertTrue($this->ticketRepository->has(1));

        $expected = [
            'ticketId'    => 1,
            'agentID'     => 1,
            'customerID'  => 2,
            'subject'     => 'Multiple Replies with a Reply Attachment',
            'description' => 'This is a support request.',
            'attachments' => [
                [
                    'url'      => 'https://ticksy_attachments.s3.amazonaws.com/basketball_692.jpg',
                    'filename' => 'basketball.jpg',
                ],
                [
                    'url'      => 'https://ticksy_attachments.s3.amazonaws.com/Zendesk-Ticksy-401_356.jpg',
                    'filename' => 'Zendesk-Ticksy-401.jpg',
                ],
                [
                    'url'      => 'https://ticksy_attachments.s3.amazonaws.com/ticksy401Postman_784.jpg',
                    'filename' => 'ticksy401Postman.jpg',
                ],
            ],
            'createdAt'   => '2017-09-18 10:35:46',
            'updatedAt'   => '2017-10-21 17:47:51',
        ];
        $this->assertSame($expected, $this->ticketRepository->get(1));
    }

    public function testReplies()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $expected = 1;
        $this->assertEquals($expected, $this->replyRepository->count());
        $this->assertTrue($this->replyRepository->has('1'));
        $this->assertTrue($this->replyRepository->has(1));

        $expected = 3;
        $this->assertCount($expected, $this->replyRepository->get(1));


        $this->assertArrayHasKey('6565705', $this->replyRepository->get(1));

        $this->assertArrayHasKey('userId', $this->replyRepository->get("1.6565705"));
        $this->assertArrayHasKey('reply', $this->replyRepository->get("1.6565705"));
        $this->assertArrayHasKey('timestamp', $this->replyRepository->get("1.6565705"));

        $expected = [
            'ticketId'    => 1,
            'userId'      => '1',
            'reply'       => '<p>Reply with an attachment is coming your way.</p>',
            'timestamp'   => '2017-10-21 17:47:51',
            'read'        => false,
            'attachments' => [],
            'replyId'     => '6565705',
        ];
        $this->assertSame($expected, $this->replyRepository->get("1.6565705"));
    }

    /**********************
     * Assemble Tests
     **********************/

    public function testAssembleBuildsATicket()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $tickets  = $dataMapper->assemble();
        $expected = 1;
        $this->assertCount($expected, $tickets);
        $this->arrayHasKey(1, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[1]);
    }

    public function testAssembledTicket()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $tickets = $dataMapper->assemble();

        $ticket = $tickets[1];
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
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');
        $tickets = $dataMapper->assemble();
        $ticket  = $tickets[1];

        $this->assertNotNull($ticket->getAgent());
        $this->assertNotNull($ticket->getCustomer());
        $this->assertInstanceOf(User::class, $ticket->getAgent());
        $this->assertInstanceOf(User::class, $ticket->getCustomer());
        $this->assertSame('Willie', $ticket->getAgent()->getFirstName());
        $this->assertSame('Sally', $ticket->getCustomer()->getFirstName());
    }

    public function testAssembledAttachment()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');

        $tickets     = $dataMapper->assemble();
        $attachments = ($tickets[1])->getAttachments();

        $expected = 3;
        $this->assertCount($expected, $attachments);

        $this->assertSame(
            [
                [
                    'url'      => 'https://ticksy_attachments.s3.amazonaws.com/basketball_692.jpg',
                    'filename' => 'basketball.jpg',
                ],
                [
                    'url'      => 'https://ticksy_attachments.s3.amazonaws.com/Zendesk-Ticksy-401_356.jpg',
                    'filename' => 'Zendesk-Ticksy-401.jpg',
                ],
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
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');

        $tickets = $dataMapper->assemble();
        $replies = ($tickets[1])->getReplies();

        $expected = 3;
        $this->assertCount($expected, $replies);

        // 1st reply
        $reply = $replies[2];
        $this->assertSame('<p>This is a reply msg...</p>', $reply['reply']);
        $this->assertSame('2017-09-18 12:23:53', $reply['date']);
        $this->assertEmpty($reply['attachments']);
        $this->assertInstanceOf(User::class, $reply['user']);
        $this->assertSame('Willie', ($reply['user'])->getFirstName());

        // 2nd reply
        $reply = $replies[1];
        $this->assertSame('This is another reply.', $reply['reply']);
        $this->assertSame('2017-09-18 12:31:02', $reply['date']);
        $this->assertEmpty($reply['attachments']);
        $this->assertInstanceOf(User::class, $reply['user']);
        $this->assertSame('Sally', ($reply['user'])->getFirstName());

        // Latest one
        $reply = $replies[0];
        $this->assertSame('<p>Reply with an attachment is coming your way.</p>', $reply['reply']);
        $this->assertSame('2017-10-21 17:47:51', $reply['date']);
        $this->assertEmpty($reply['attachments']);
        $this->assertInstanceOf(User::class, $reply['user']);
        $this->assertSame('Willie', ($reply['user'])->getFirstName());
    }

    public function testAssembledHistory()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticksy.json'), 'open-tickets');

        $tickets = $dataMapper->assemble();
        $history = ($tickets[1])->getHistory();

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
