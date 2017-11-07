<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\Root;

use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\AttachmentMapper;
use Pressware\AwesomeSupport\API\Repository\TicketRepository;
use Pressware\AwesomeSupport\Tests\ZAPI\NotifierStub;

class AttachmentMapperWithRepTest extends TestCase
{
    protected $notifier;
    protected $mapper;
    protected $message;
    protected $index = 0;
    protected $attachments = [];

    public function setUp()
    {
        parent::setUp();

        $this->message  = 'The following attachment has an invalid URL and was not been imported: ';
        $this->notifier = new NotifierStub();

        $this->mapper = new AttachmentMapper(
            [
                'invalidAttachment' => $this->message,
            ],
            $this->notifier
        );
        $this->index  = 0;
    }

    public function testFailsWithNoAttachments()
    {
        $attachments      = [];
        $ticketRepository = new TicketRepository($this->notifier);

        $expected = 0;
        $this->assertEquals(
            $expected,
            $this->mapper->map($attachments, 1, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $attachments = null;
        $expected    = 0;
        $this->assertEquals(
            $expected,
            $this->mapper->map($attachments, 10, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $attachments = new \stdClass();
        $expected    = 0;
        $this->assertEquals(
            $expected,
            $this->mapper->map($attachments, 20, $ticketRepository, [$this, 'attachmentCallback'])
        );
    }

    public function testValidImage()
    {
        $this->attachments = [
            [
                'url'      => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/WordPress_logo.svg/' .
                    '2000px-WordPress_logo.svg.png',
                'filename' => '2000px-WordPress_logo.svg.png',
            ],
        ];

        $ticketRepository = new TicketRepository($this->notifier, [
            17 => [
                'description' => 'This is a comment.',
            ],
        ]);

        $expected = 1;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 17, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $this->assertSame('This is a comment.', $ticketRepository->get('17.description'));
        $this->assertSame($this->attachments, $ticketRepository->get('17.attachments'));
    }

    public function testValidImageNoTicket()
    {
        $this->attachments = [
            [
                'url'      => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/WordPress_logo.svg/' .
                    '2000px-WordPress_logo.svg.png',
                'filename' => '2000px-WordPress_logo.svg.png',
            ],
        ];

        $ticketRepository = new TicketRepository($this->notifier);

        $expected = 1;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 27, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $this->assertSame('', $ticketRepository->get('27.description'));
        $this->assertSame($this->attachments, $ticketRepository->get('27.attachments'));
    }

    public function testImageAsQuery()
    {
        $this->attachments = [
            [
                'url'      => 'https://wbakertest.zendesk.com/attachments/token/4SEWUie6QPwQlydUpOXza1Qje/' .
                    '?name=elePHPant.jpg',
                'filename' => 'elePHPant.jpg',
            ],
        ];

        $ticketRepository = new TicketRepository($this->notifier, [
            37 => [
                'description' => 'This is a comment.',
            ],
        ]);

        $expected = 1;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 37, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $this->assertSame('This is a comment.', $ticketRepository->get('37.description'));
        $this->assertSame($this->attachments, $ticketRepository->get('37.attachments'));
    }

    public function testValidImages()
    {
        $this->attachments = [
            [
                'url'      => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/' .
                    'WordPress_logo.svg/2000px-WordPress_logo.svg.png',
                'filename' => '2000px-WordPress_logo.svg.png',
            ],
            [
                'url'      => 'https://s.w.org/about/images/logos/wordpress-logo-simplified-rgb.png',
                'filename' => 'wordpress-logo-simplified-rgb.png',
            ],
        ];

        $ticketRepository = new TicketRepository($this->notifier, [
            25 => [
                'description' => 'This is a comment for Ticket #25.',
            ],
        ]);

        $expected = 1;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 25, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $this->assertSame(
            'This is a comment for Ticket #25.',
            $ticketRepository->get('25.description')
        );
        $this->assertSame($this->attachments, $ticketRepository->get('25.attachments'));
    }

    public function testImageWithSpaces()
    {
        $this->attachments = [
            [
                'url'      => 'https://example.com/Image With Spaces.jpg',
                'filename' => 'Image With Spaces.jpg',
            ],
        ];

        $ticketRepository = new TicketRepository($this->notifier, [
            2 => [
                'description' => 'This is a comment for Ticket #2.',
            ],
        ]);

        $expected = 1;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 2, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $this->assertSame(
            'This is a comment for Ticket #2.',
            $ticketRepository->get('2.description')
        );
        $this->assertSame([
            [
                'url'      => 'https://example.com/Image+With+Spaces.jpg',
                'filename' => 'Image+With+Spaces.jpg',
            ],
        ], $ticketRepository->get('2.attachments'));
    }

    public function testNoExtension()
    {
        $this->attachments = [
            [
                'url'      => 'https://example.com/some-file',
                'filename' => 'some-file',
            ],
        ];

        $ticketRepository = new TicketRepository($this->notifier, [
            2 => [
                'description' => 'This is a comment.',
            ],
        ]);

        $expected = 2;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 2, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $this->assertNull($ticketRepository->get('2.attachments'));

        $expected = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            'This is a comment.',
            $this->message,
            $this->attachments[0]['url']
        );
        $this->assertSame($expected, $ticketRepository->get('2.description'));
    }

    public function testCodeExtension()
    {
        $this->attachments = [
            [
                'url'      => 'https://awesomesupport.com/style.css',
                'filename' => 'style.css',
            ],
        ];

        $ticketRepository = new TicketRepository($this->notifier, [
            2 => [
                'description' => 'This is a comment.',
            ],
        ]);

        $expected = 2;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 2, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $this->assertNull($ticketRepository->get('2.attachments'));

        $expected = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            'This is a comment.',
            $this->message,
            $this->attachments[0]['url']
        );
        $this->assertSame($expected, $ticketRepository->get('2.description'));
    }


    public function testInvalidUrls()
    {
        $this->attachments = [
            [
                'url'      => 'http://foo.notld/image.png',
                'filename' => 'image.png',
            ],
            [
                'url'      => 'htp:/awesomesupport.com/wp-content/uploads/2016/11/AwesomeSupport_230x69px.png',
                'filename' => 'AwesomeSupport_230x69px.png',
            ],
        ];

        $ticketRepository = new TicketRepository($this->notifier, [
            2 => [
                'description' => 'This is a comment.',
            ],
        ]);

        $expected = 2;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 2, $ticketRepository, [$this, 'attachmentCallback'])
        );

        $this->assertNull($ticketRepository->get('2.attachments'));

        $expected = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            'This is a comment.',
            $this->message,
            $this->attachments[0]['url']
        );
        $expected = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            $expected,
            $this->message,
            $this->attachments[1]['url']
        );
        $this->assertSame($expected, $ticketRepository->get('2.description'));
    }

    public function attachmentCallback($attachment)
    {
        $this->assertSame($this->attachments[$this->index], $attachment);
        $this->index++;
        return $attachment;
    }
}
