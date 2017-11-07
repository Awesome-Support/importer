<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\ZProviders\HelpScout;

use Mockery;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\AttachmentMapper;
use Pressware\AwesomeSupport\API\Provider\HelpScout\DataMapper;
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
        $dataMapper->init($this->dates, 'Help Scout and PHPUnit');
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
        $json       = $this->getJson('conversation.json');
        $this->assertInstanceOf('stdClass', $dataMapper->fromJSON($json));
    }

    public function testToArray()
    {
        $dataMapper = $this->createDataMapper();
        $this->assertSame(['Hi there'], $dataMapper->toArray('Hi there'));

        $json = $dataMapper->fromJSON($this->getJson('conversation.json'));
        $this->assertTrue(is_array($dataMapper->toArray($json)));
    }

    public function testSkipsForEndDate()
    {
        // "modifiedAt": "2017-11-01T23:10:55Z",

        // Test that it skips this ticket as the date is after the expected endDate.
        $this->dates['endDate'] = '2017-10-31';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));
        $this->assertEmpty($this->ticketRepository->getAll());

        // Test that it skips this ticket as the ending timestamp is 1 minute before the ticket
        $this->dates['endDate'] = '2017-11-01 23:09:55';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));
        $this->assertEmpty($this->ticketRepository->getAll());
    }

    public function testWithinEndDate()
    {
        // "modifiedAt": "2017-11-01T23:10:55Z",

        // Test that it includes the ticket (doesn't skip it).
        $this->dates['endDate'] = '2017-11-01 23:11:55';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
    }

    public function testWithNoDates()
    {
        // "time_stamp": "2017-09-18 10:35:46",

        $this->dates['startDate'] = '';
        $this->dates['endDate']   = '';
        $dataMapper               = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
    }

    public function testUserMapping()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));

        $expected = 2;
        $this->assertCount($expected, $this->userRepository->getAll());

        $user = $this->userRepository->get('1');
        $this->assertInstanceOf('Pressware\AwesomeSupport\Entity\User', $user);
        $this->assertSame('Willie', $user->getFirstName());
        $this->assertSame('Mays', $user->getLastName());
        $this->assertSame('wmays@example.com', $user->getEmail());
        $this->assertSame(UserRoles::AGENT, $user->getRole());

        $user = $this->userRepository->get('2');
        $this->assertInstanceOf('Pressware\AwesomeSupport\Entity\User', $user);
        $this->assertSame('Barry', $user->getFirstName());
        $this->assertSame('White', $user->getLastName());
        $this->assertSame('barrywhite@gmail.com', $user->getEmail());
        $this->assertSame(UserRoles::CUSTOMER, $user->getRole());
    }

    public function testTicketMapping()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
        $this->assertTrue($this->ticketRepository->has('458887447'));
        $this->assertTrue($this->ticketRepository->has(458887447));

        $encodedFile = urlencode('Screen Shot 2017-10-31 at 12.50.11 PM.png');
        $expected    = [
            'ticketId'    => 458887447,
            'agentID'     => 1,
            'customerID'  => 2,
            'subject'     => 'Foo Ticket',
            'description' => 'This is a support request using Help Scout.',
            'attachments' => [
                [
                    'url'      => 'https://secure.helpscout.net/file/98848150/18c19af0a8121280adfa6971a3503305dd0f2145/'
                        . $encodedFile,
                    'filename' => $encodedFile,
                ],
            ],
            'createdAt'   => '2017-10-31T16:54:00Z',
            'updatedAt'   => '2017-11-01T23:10:55Z',
        ];
        $this->assertSame($expected, $this->ticketRepository->get(458887447));
    }

    public function testReplies()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));
        $expected = 1;
        $this->assertEquals($expected, $this->replyRepository->count());
        $this->assertTrue($this->replyRepository->has('458887447'));
        $this->assertTrue($this->replyRepository->has(458887447));

        $expected = 2;
        $this->assertCount($expected, $this->replyRepository->get(458887447));

        $this->assertArrayHasKey('1244395380', $this->replyRepository->get(458887447));

        $this->assertArrayHasKey('userId', $this->replyRepository->get("458887447.1244395380"));
        $this->assertArrayHasKey('reply', $this->replyRepository->get("458887447.1244395380"));
        $this->assertArrayHasKey('timestamp', $this->replyRepository->get("458887447.1244395380"));

        $expected = [
            'ticketId'    => 458887447,
            'userId'      => 1,
            'reply'       => 'Hey Barry, Thank you for your questions and the awesome screenshots.',
            'timestamp'   => '2017-10-31T22:27:33Z',
            'read'        => false,
            'attachments' => [
                [
                    'url'      => 'https://s.w.org/about/images/logos/wordpress-logo-simplified-rgb.png',
                    'filename' => 'wordpress-logo-simplified-rgb.png',
                ],
            ],
            'replyId'     => 1244395380,
        ];
        $this->assertSame($expected, $this->replyRepository->get("458887447.1244395380"));
    }

    /**********************
     * Assemble Tests
     **********************/

    public function testAssembleBuildsATicket()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));
        $tickets  = $dataMapper->assemble();
        $expected = 1;
        $this->assertCount($expected, $tickets);
        $this->arrayHasKey(458887447, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[458887447]);
    }

    public function testAssembledTicket()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));
        $tickets = $dataMapper->assemble();

        $ticket = $tickets[458887447];
        $this->assertSame(
            'Foo Ticket',
            $ticket->getSubject()
        );
        $this->assertSame(
            'This is a support request using Help Scout.',
            $ticket->getDescription()
        );
        $this->assertSame('Help Scout and PHPUnit', $ticket->getSource());
    }

    public function testAssembledUser()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));
        $tickets = $dataMapper->assemble();
        $ticket  = $tickets[458887447];

        $this->assertNotNull($ticket->getAgent());
        $this->assertNotNull($ticket->getCustomer());
        $this->assertInstanceOf(User::class, $ticket->getAgent());
        $this->assertInstanceOf(User::class, $ticket->getCustomer());
        $this->assertSame('Willie', $ticket->getAgent()->getFirstName());
        $this->assertSame('Barry', $ticket->getCustomer()->getFirstName());
    }

    public function testAssembledAttachment()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));

        $tickets = $dataMapper->assemble();
        $expected = 1;
        $this->assertCount($expected, ($tickets[458887447])->getAttachments());
        $encodedFile = urlencode('Screen Shot 2017-10-31 at 12.50.11 PM.png');
        $this->assertSame(
            [
                [
                    'url'      => 'https://secure.helpscout.net/file/98848150/18c19af0a8121280adfa6971a3503305dd0f2145/'
                        . $encodedFile,
                    'filename' => $encodedFile,
                ],
            ],
            ($tickets[458887447])->getAttachments()
        );


        $replies = ($tickets[458887447])->getReplies();

        $expected = 1;
        $this->assertCount($expected, $replies[1]['attachments']);
        $this->assertSame(
            [
                [
                    'url'      => 'https://s.w.org/about/images/logos/wordpress-logo-simplified-rgb.png',
                    'filename' => 'wordpress-logo-simplified-rgb.png',
                ],
            ],
            $replies[1]['attachments']
        );
    }

    public function testAssembledReplies()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));

        $tickets = $dataMapper->assemble();
        $replies = ($tickets[458887447])->getReplies();

        $expected = 2;
        $this->assertCount($expected, $replies);

        // 1st reply
        $reply = $replies[1];
        $this->assertSame('Hey Barry, Thank you for your questions and the awesome screenshots.', $reply['reply']);
        $this->assertSame('2017-10-31T22:27:33Z', $reply['date']);
        $this->assertSame(
            [
                [
                    'url'      => 'https://s.w.org/about/images/logos/wordpress-logo-simplified-rgb.png',
                    'filename' => 'wordpress-logo-simplified-rgb.png',
                ],
            ],
            $reply['attachments']
        );
        $this->assertInstanceOf(User::class, $reply['user']);
        $this->assertSame('Willie', ($reply['user'])->getFirstName());

        // 2nd reply
        $reply = $replies[0];
        $this->assertSame('Yo Barry, Ignore this one. Just playing around.....', $reply['reply']);
        $this->assertSame('2017-10-31T23:10:55Z', $reply['date']);
        $this->assertEmpty($reply['attachments']);
        $this->assertInstanceOf(User::class, $reply['user']);
        $this->assertSame('Willie', ($reply['user'])->getFirstName());
    }

    public function testAssembledHistory()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('conversation.json'));

        $tickets = $dataMapper->assemble();
        $history = ($tickets[458887447])->getHistory();

        $expected = 2;
        $this->assertCount($expected, $history);

        // 1st open - open
        $this->assertInstanceOf(User::class, $history[0]['user']);
        $this->assertSame('Barry', ($history[0]['user'])->getFirstName());
        $this->assertSame('open', $history[0]['value']);
        $this->assertSame('2017-10-31T16:54:00Z', $history[0]['date']);

        // 2nd one Pending
        $this->assertInstanceOf(User::class, $history[1]['user']);
        $this->assertSame('Willie', ($history[1]['user'])->getFirstName());
        $this->assertSame('processing', $history[1]['value']);
        $this->assertSame('2017-11-01T00:54:12Z', $history[1]['date']);
    }

    /********************
     * Helpers
     ********************/

    protected function getJson($jsonFile)
    {
        return file_get_contents(__DIR__ . '/test-data/' . $jsonFile, true);
    }
}
