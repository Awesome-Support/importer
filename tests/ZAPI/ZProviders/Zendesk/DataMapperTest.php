<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\ZProviders\Zendesk;

use Mockery;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\AttachmentMapper;
use Pressware\AwesomeSupport\API\Provider\Zendesk\DataMapper;
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
                'startDate' => '2017-10-01 00:00:00',
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
        $dataMapper->init($this->dates, 'Zendesk and PHPUnit');
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
        $user       = $this->getJson('user.json');
        $this->assertInstanceOf('stdClass', $dataMapper->fromJSON($user));
    }

    public function testToArray()
    {
        $dataMapper = $this->createDataMapper();
        $this->assertSame(['Hi there'], $dataMapper->toArray('Hi there'));

        $user = $dataMapper->fromJSON($this->getJson('user.json'));
        $this->assertTrue(is_array($dataMapper->toArray($user)));
    }

    public function testSkipsForEndDate()
    {
        // The update_at data for the test sample is: "updated_at": "2017-10-16T01:27:36Z"

        // Test that it skips this ticket as the date is after the expected endDate.
        $this->dates['endDate'] = '2017-10-15';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');
        $this->assertEmpty($this->ticketRepository->getAll());

        // Test that it skips this ticket as the ending timestamp is 1 minute before the ticket
        $this->dates['endDate'] = '2017-10-16 1:26:36';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');
        $this->assertEmpty($this->ticketRepository->getAll());
    }

    public function testWithinEndDate()
    {
        // The update_at data for the test sample is: "updated_at": "2017-10-16T01:27:36Z"

        // Test that it includes the ticket (doesn't skip it).
        $this->dates['endDate'] = '2017-10-16 23:59:59';
        $dataMapper             = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
    }

    public function testUserMapping()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');
        $expected = 2;
        $this->assertCount($expected, $this->userRepository->getAll());

        $user = $this->userRepository->get('1');
        $this->assertInstanceOf('Pressware\AwesomeSupport\Entity\User', $user);
        $this->assertSame('Bob', $user->getFirstName());
        $this->assertSame('Jones', $user->getLastName());
        $this->assertSame('bob.jones@gmail.com', $user->getEmail());
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
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');
        $expected = 1;
        $this->assertCount($expected, $this->ticketRepository->getAll());
        $this->assertTrue($this->ticketRepository->has('656'));
        $this->assertTrue($this->ticketRepository->has(656));

        $expected = [
            'ticketId'    => 656,
            'agentID'     => 1,
            'customerID'  => 2,
            'subject'     => 'Ticket with an attachment',
            'description' => 'This is a dummy ticket that has an attachment.  Need to see how the attachments work.',
            'attachments' => null,
            'createdAt'   => '2017-10-14 01:05:58',
            'updatedAt'   => '2017-10-16 01:27:36',
        ];
        $this->assertSame($expected, $this->ticketRepository->get(656));
    }

    public function testTicketEvents()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('ticketEvents.json'), 'ticketEvents');
        $expected = 1;
        $this->assertEquals($expected, $this->ticketRepository->count());
        $this->assertTrue($this->ticketRepository->has('656'));
        $this->assertTrue($this->ticketRepository->has(656));

        $this->assertEquals($expected, $this->replyRepository->count());
        $this->assertTrue($this->replyRepository->has('656'));
        $this->assertTrue($this->replyRepository->has(656));

        $expected = 1;
        $this->assertCount($expected, $this->replyRepository->get(656));


        $this->assertArrayHasKey('134854884054', $this->replyRepository->get(656));

        $this->assertArrayHasKey('userId', $this->replyRepository->get("656.134854884054"));
        $this->assertArrayHasKey('reply', $this->replyRepository->get("656.134854884054"));
        $this->assertArrayHasKey('timestamp', $this->replyRepository->get("656.134854884054"));

        $expected = [
            '134854884054' => [
                'ticketId'    => 656,
                'userId'      => 1,
                'reply'       => 'Reply #1

Lorem ipsum dolor sit amet, consectetur adipiscing elit. ' .
                    'Pellentesque at tempor orci. Sed non aliquet eros. Quisque scelerisque odio a ' .
                    'nibh dignissim, non condimentum sem feugiat. Mauris eu odio eros. Mauris bibendum ' .
                    'velit sed laoreet feugiat. Vivamus placerat mi sit amet vestibulum molestie. ' .
                    'Aenean sagittis sit amet lorem a posuere. Donec vel augue vitae leo pulvinar ' .
                    'iaculis id ut augue. Sed gravida tellus vestibulum libero vestibulum, non venenatis ' .
                    'felis imperdiet. In eget augue vehicula, gravida nisi sed, aliquam magna. Nullam ' .
                    'vestibulum arcu ipsum, et pulvinar tellus dignissim quis.',
                'timestamp'   => '2017-10-16 01:27:36',
                'read'        => false,
                'attachments' => [],
            ],
        ];

        $this->assertSame($expected, $this->replyRepository->get(656));
    }

    /**********************
     * Assemble Tests
     **********************/

    public function testAssembleBuildsATicket()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');
        $tickets  = $dataMapper->assemble();
        $expected = 1;
        $this->assertCount($expected, $tickets);
        $this->arrayHasKey(656, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[656]);
    }

    public function testAssembledTicket()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');
        $tickets = $dataMapper->assemble();

        $ticket = $tickets[656];
        $this->assertSame('Ticket with an attachment', $ticket->getSubject());
        $this->assertSame(
            'This is a dummy ticket that has an attachment.  ' .
            'Need to see how the attachments work.',
            $ticket->getDescription()
        );
        $this->assertSame('Zendesk and PHPUnit', $ticket->getSource());
    }

    public function testAssembledUser()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');
        $tickets = $dataMapper->assemble();
        $ticket  = $tickets[656];

        $this->assertNotNull($ticket->getAgent());
        $this->assertNotNull($ticket->getCustomer());
        $this->assertInstanceOf(User::class, $ticket->getAgent());
        $this->assertInstanceOf(User::class, $ticket->getCustomer());
        $this->assertSame('Bob', $ticket->getAgent()->getFirstName());
        $this->assertSame('Sally', $ticket->getCustomer()->getFirstName());
    }

    public function testAssembledAttachment()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');

        $tickets = $dataMapper->assemble();
        $ticket  = $tickets[656];
        $this->assertNull($ticket->getAttachments()); // We haven't load attachments (events) yet.

        $dataMapper->mapJSON($this->getJson('ticketEvents.json'), 'ticketEvents');
        $tickets     = $dataMapper->assemble();
        $attachments = ($tickets[656])->getAttachments();
        $expected    = 1;

        $this->assertCount($expected, $attachments);
        $this->assertArrayHasKey('url', $attachments[0]);
        $this->assertSame([
            'url'      => 'https://wbakertest.zendesk.com/attachments/token/4SEWUie6QPwQlydUpOXza1Qje/' .
                '?name=elePHPant.jpg',
            'filename' => 'elePHPant.jpg',
        ], $attachments[0]);
    }

    public function testAssembledReplies()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');

        $tickets = $dataMapper->assemble();
        $ticket  = $tickets[656];
        $this->assertNull($ticket->getReplies()); // We haven't load replies (events) yet.

        $dataMapper->mapJSON($this->getJson('ticketEvents.json'), 'ticketEvents');
        $tickets = $dataMapper->assemble();
        $replies = ($tickets[656])->getReplies();

        $expected = 1;
        $this->assertCount($expected, $replies);
        $reply = array_shift($replies);

        $expected = 'Reply #1

Lorem ipsum dolor sit amet, consectetur adipiscing elit. ' .
            'Pellentesque at tempor orci. Sed non aliquet eros. Quisque scelerisque odio a ' .
            'nibh dignissim, non condimentum sem feugiat. Mauris eu odio eros. Mauris bibendum ' .
            'velit sed laoreet feugiat. Vivamus placerat mi sit amet vestibulum molestie. ' .
            'Aenean sagittis sit amet lorem a posuere. Donec vel augue vitae leo pulvinar ' .
            'iaculis id ut augue. Sed gravida tellus vestibulum libero vestibulum, non venenatis ' .
            'felis imperdiet. In eget augue vehicula, gravida nisi sed, aliquam magna. Nullam ' .
            'vestibulum arcu ipsum, et pulvinar tellus dignissim quis.';
        $this->assertSame($expected, $reply['reply']);
        $this->assertSame('2017-10-16 01:27:36', $reply['date']);
        $this->assertEmpty($reply['attachments']);
        $this->assertInstanceOf(User::class, $reply['user']);
        $this->assertSame('Bob', ($reply['user'])->getFirstName());
    }

    public function testAssembledHistory()
    {
        $dataMapper = $this->createDataMapper();
        $dataMapper->mapJSON($this->getJson('tickets.json'), 'tickets');

        $tickets = $dataMapper->assemble();
        $ticket  = $tickets[656];
        $this->assertNull($ticket->getHistory()); // We haven't load history (events) yet.

        $dataMapper->mapJSON($this->getJson('ticketEvents.json'), 'ticketEvents');
        $tickets = $dataMapper->assemble();
        $history = ($tickets[656])->getHistory();

        $expected = 3;
        $this->assertCount($expected, $history);

        // 1st
        $this->assertInstanceOf(User::class, $history[0]['user']);
        $this->assertSame('Sally', ($history[0]['user'])->getFirstName());
        $this->assertSame('open', $history[0]['value']);
        $this->assertSame('2017-10-14 01:05:58', $history[0]['date']);

        // 2nd
        $this->assertInstanceOf(User::class, $history[1]['user']);
        $this->assertSame('Bob', ($history[1]['user'])->getFirstName());
        $this->assertSame('processing', $history[1]['value']);
        $this->assertSame('2017-10-16 01:27:36', $history[1]['date']);

        // 3rd
        $this->assertInstanceOf(User::class, $history[2]['user']);
        $this->assertSame('Bob', ($history[2]['user'])->getFirstName());
        $this->assertSame('closed', $history[2]['value']);
        $this->assertSame('2017-10-16 01:27:36', $history[2]['date']);
    }

    /********************
     * Helpers
     ********************/

    protected function getJson($jsonFile)
    {
        return file_get_contents(__DIR__ . '/test-data/' . $jsonFile, true);
    }
}
