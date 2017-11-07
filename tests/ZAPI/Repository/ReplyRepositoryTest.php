<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\Repository;

use Mockery;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Repository\ReplyRepository;

class ReplyRepositoryTest extends TestCase
{
    protected $replies;
    protected $repository;

    public function setUp()
    {
        $this->replies = [
            123 => [
                'ticketId'    => 42,
                'userId'      => 1,
                'reply'       => 'This is a reply.',
                'timestamp'   => '2017-11-01T10:45:02Z',
                'read'        => false,
                'attachments' => [],
            ],
        ];
        $this->repository = new ReplyRepository(
            Mockery::mock('Pressware\AwesomeSupport\Notifications\Notifier'),
            $this->replies
        );

        parent::setUp();
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(ReplyRepository::class, $this->repository);
    }

    public function testHasIsTrue()
    {
        $this->assertTrue($this->repository->has(42));
        $this->assertTrue($this->repository->has('42.123'));
    }

    public function testHasIsFalse()
    {
        $this->assertFalse($this->repository->has(1));
        $this->assertFalse($this->repository->has('42.987'));
    }

    public function testGet()
    {
        $this->assertSame($this->replies[123], $this->repository->get('42.123'));
    }

    public function testSetReply()
    {
        $this->repository->set('42.123.reply', 'Foo');
        $this->assertSame('Foo', $this->repository->get('42.123.reply'));
    }

    public function testSetAttachment()
    {
        $attachment = [
            'url' => 'http://example.com/image.jpg',
            'filename' => 'image.jpg',
        ];
        $this->repository->setAttachment(42, 123, $attachment);
        $this->assertSame([$attachment], $this->repository->get("42.123.attachments"));
    }

    public function testSetAttachmentNoTicket()
    {
        $attachment = [
            'url' => 'http://example.com/image.jpg',
            'filename' => 'image.jpg',
        ];
        $this->repository->setAttachment(2, 555, $attachment);
        $this->assertSame([$attachment], $this->repository->get("2.555.attachments"));
    }

    public function testSetAttachmentDuplicate()
    {
        $attachment = [
            'url' => 'http://example.com/image.jpg',
            'filename' => 'image.jpg',
        ];
        $this->repository->setAttachment(42, 123, $attachment);
        $this->repository->setAttachment(42, 123, $attachment);
        $this->assertSame([$attachment], $this->repository->get("42.123.attachments"));
    }

    public function testCreate()
    {
        $reply = [
            'ticketId'    => 25,
            'userId'      => 600,
            'reply'       => 'Yo, I need help with xyz.',
            'timestamp'   => '2016-11-01 10:00:05',
            'read'        => false,
            'attachments' => [
                'url' => 'http://example.com/image.jpg',
                'filename' => 'image.jpg',
            ],
        ];
        $this->repository->create(25, 999, $reply);
        $this->assertTrue($this->repository->has('25.999'));
        $this->assertSame('Yo, I need help with xyz.', $this->repository->get("25.999.reply"));
    }

    public function testGetAll()
    {
        $replies = [42 => $this->replies];
        $this->assertSame($replies, $this->repository->getAll());
        $reply = [
            'ticketId'    => 42,
            'userId'      => 600,
            'reply'       => 'Yo, I need help with xyz.',
            'timestamp'   => '2016-11-01 10:00:05',
            'read'        => false,
            'attachments' => [
                'url' => 'http://example.com/image.jpg',
                'filename' => 'image.jpg',
            ],
        ];
        $replies[42][999] = $reply;
        $this->repository->create(42, 999, $reply);
        $this->assertSame($replies, $this->repository->getAll());
    }
}
