<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\Repository;

use Mockery;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Repository\Repository;
use Pressware\AwesomeSupport\API\Repository\TicketRepository;

class TicketRepositoryTest extends TestCase
{
    protected $tickets;
    protected $repository;

    public function setUp()
    {
        $this->tickets = [
            42 => [
                'ticketId'    => 42,
                'agentID'     => 500,
                'customerID'  => 600,
                'subject'     => 'Help',
                'description' => 'I need help with xyz.',
                'attachments' => null,
                'createdAt'   => '2017-11-01 10:00:05',
                'updatedAt'   => '',
            ],
        ];
        $this->repository = new TicketRepository(
            Mockery::mock('Pressware\AwesomeSupport\Notifications\Notifier'),
            $this->tickets
        );

        parent::setUp();
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(TicketRepository::class, $this->repository);
    }

    public function testHasIsTrue()
    {
        $this->assertTrue($this->repository->has(42));
    }

    public function testHasIsFalse()
    {
        $this->assertFalse($this->repository->has(1));
    }

    public function testGet()
    {
        $this->assertSame($this->tickets[42], $this->repository->get(42));
    }

    public function testSetDescription()
    {
        $this->repository->setDescription(42, 'Foo');
        $this->assertSame('Foo', $this->repository->get("42.description"));
    }

    public function testSetAttachment()
    {
        $attachment = [
            'url' => 'http://example.com/image.jpg',
            'filename' => 'image.jpg',
        ];
        $this->repository->setAttachment(42, $attachment);
        $this->assertSame([$attachment], $this->repository->get("42.attachments"));
    }

    public function testSetAttachmentNoTicket()
    {
        $attachment = [
            'url' => 'http://example.com/image.jpg',
            'filename' => 'image.jpg',
        ];
        $this->repository->setAttachment(2, $attachment);
        $this->assertSame([$attachment], $this->repository->get("2.attachments"));
    }

    public function testSetAttachmentDuplicate()
    {
        $attachment = [
            'url' => 'http://example.com/image.jpg',
            'filename' => 'image.jpg',
        ];
        $this->repository->setAttachment(42, $attachment);
        $this->repository->setAttachment(42, $attachment);
        $this->assertSame([$attachment], $this->repository->get("42.attachments"));
    }

    public function testCreate()
    {
        $ticket = [
            'ticketId'    => 25,
            'agentID'     => 500,
            'customerID'  => 600,
            'subject'     => 'Help Me',
            'description' => 'Yo, I need help with xyz.',
            'attachments' => null,
            'createdAt'   => '2016-11-01 10:00:05',
            'updatedAt'   => '',
        ];
        $this->repository->set(25, $ticket);
        $this->assertTrue($this->repository->has(25));
        $this->assertSame('Help Me', $this->repository->get("25.subject"));
    }

    public function testGetAll()
    {
        $this->assertSame($this->tickets, $this->repository->getAll());
        $ticket = [
            'ticketId'    => 25,
            'agentID'     => 500,
            'customerID'  => 600,
            'subject'     => 'Help Me',
            'description' => 'Yo, I need help with xyz.',
            'attachments' => null,
            'createdAt'   => '2016-11-01 10:00:05',
            'updatedAt'   => '',
        ];
        $this->tickets[25] = $ticket;
        $this->repository->set(25, $ticket);
        $this->assertSame($this->tickets, $this->repository->getAll());
    }
}
