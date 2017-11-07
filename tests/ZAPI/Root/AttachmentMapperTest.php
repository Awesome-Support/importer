<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\Root;

use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\AttachmentMapper;
use Pressware\AwesomeSupport\Tests\ZAPI\NotifierStub;

class AttachmentMapperTest extends TestCase
{
    protected $mapper;
    protected $message;
    protected $index = 0;
    protected $attachments = [];

    public function setUp()
    {
        parent::setUp();

        $this->message = 'The following attachment has an invalid URL and was not been imported: ';
        $this->mapper  = new AttachmentMapper(
            [
                'invalidAttachment' => $this->message,
            ],
            new NotifierStub()
        );
        $this->index   = 0;
    }

    public function testFailsWithNoAttachments()
    {
        $attachments = [];
        $repMock     = \Mockery::mock('Pressware\AwesomeSupport\API\Repository\TicketRepository');

        $expected = 0;
        $this->assertEquals(
            $expected,
            $this->mapper->map($attachments, 1, $repMock, [$this, 'attachmentCallback'])
        );

        $attachments = null;
        $expected    = 0;
        $this->assertEquals(
            $expected,
            $this->mapper->map($attachments, 10, $repMock, [$this, 'attachmentCallback'])
        );

        $attachments = new \stdClass();
        $expected    = 0;
        $this->assertEquals(
            $expected,
            $this->mapper->map($attachments, 20, $repMock, [$this, 'attachmentCallback'])
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

        $repMock    = \Mockery::mock('Pressware\AwesomeSupport\API\Repository\TicketRepository')->makePartial();
        $newComment = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            'This is the comment',
            $this->message,
            $this->attachments[0]['url']
        );
        $repMock->shouldReceive('get')
            ->once()
            ->with('1.description')
            ->andReturn('This is the comment');

        $repMock->shouldReceive('setAttachment')
            ->once()
            ->with(1, $newComment);

        $expected = 1;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 1, $repMock, [$this, 'attachmentCallback'])
        );
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

        $repMock    = \Mockery::mock('Pressware\AwesomeSupport\API\Repository\TicketRepository')->makePartial();
        $newComment = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            'This is the comment',
            $this->message,
            $this->attachments[0]['url']
        );
        $repMock->shouldReceive('get')
            ->once()
            ->with('2.description')
            ->andReturn('This is the comment');

        $repMock->shouldReceive('setAttachment')
            ->once()
            ->with(2, $newComment);

        $expected = 1;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 2, $repMock, [$this, 'attachmentCallback'])
        );
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

        $repMock = \Mockery::mock('Pressware\AwesomeSupport\API\Repository\TicketRepository')->makePartial();

        // 1st image
        $repMock->shouldNotReceive('get');
        $repMock->shouldReceive('setAttachment')
            ->once()
            ->with(5, $this->attachments[0]);

        // 2nd image
        $repMock->shouldNotReceive('get');
        $repMock->shouldReceive('setAttachment')
            ->once()
            ->with(5, $this->attachments[1]);

        $expected = 1;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 5, $repMock, [$this, 'attachmentCallback'])
        );
    }

    public function testImageWithSpaces()
    {
        $this->attachments = [
            [
                'url'      => 'https://example.com/Image With Spaces.jpg',
                'filename' => 'Image With Spaces.jpg',
            ],
        ];

        $repMock = \Mockery::mock('Pressware\AwesomeSupport\API\Repository\TicketRepository')->makePartial();
        $repMock->create(10, []);
        $repMock->shouldNotReceive('get');
        $repMock->shouldReceive('setAttachment')
            ->once()
            ->with(10, [
                'url'      => 'https://example.com/Image+With+Spaces.jpg',
                'filename' => 'Image+With+Spaces.jpg',
            ]);

        $actual   = $this->mapper->map($this->attachments, 10, $repMock, [$this, 'attachmentCallback']);
        $expected = 1;
        $this->assertEquals($expected, $actual);
    }

    public function testNoExtension()
    {
        $this->attachments = [
            [
                'url'      => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/' .
                    'WordPress_logo.svg/2000px-WordPress_logo',
                'filename' => '2000px-WordPress_logo',
            ],
            [
                'url'      => 'https://s.w.org/about/images/logos/wordpress-logo-simplified-rgb',
                'filename' => 'wordpress-logo-simplified-rgb.png',
            ],
        ];

        $repMock = \Mockery::mock('Pressware\AwesomeSupport\API\Repository\TicketRepository')->makePartial();

        // 1st image
        $newComment = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            'This is the comment',
            $this->message,
            $this->attachments[0]['url']
        );
        $repMock->shouldReceive('get')
            ->once()
            ->with('5.description')
            ->andReturn('This is the comment');
        $repMock->shouldNotReceive('setAttachment');
        $repMock->shouldReceive('set')
            ->once()
            ->with(5, $newComment);

        // 2nd image
        $repMock->shouldReceive('get')
            ->once()
            ->with('5.description')
            ->andReturn($newComment);
        $newComment = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            $newComment,
            $this->message,
            $this->attachments[1]['url']
        );
        $repMock->shouldNotReceive('setAttachment');
        $repMock->shouldReceive('set')
            ->once()
            ->with(5, $newComment);

        $expected = 2;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 5, $repMock, [$this, 'attachmentCallback'])
        );
    }


    public function testInvalidExtension()
    {
        $this->attachments = [
            [
                'url'      => 'https://wordpress.org/index.php',
                'filename' => 'index.php',
            ],
            [
                'url'      => 'https://wordpress.org/wp4.css',
                'filename' => 'wp4.css',
            ],
        ];

        $repMock = \Mockery::mock('Pressware\AwesomeSupport\API\Repository\TicketRepository')->makePartial();

        // 1st file
        $newComment = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            'This is the comment',
            $this->message,
            $this->attachments[0]['url']
        );
        $repMock->shouldReceive('get')
            ->once()
            ->with('5.description')
            ->andReturn('This is the comment');
        $repMock->shouldNotReceive('setAttachment');
        $repMock->shouldReceive('set')
            ->once()
            ->with(5, $newComment);

        // 2nd file
        $repMock->shouldReceive('get')
            ->once()
            ->with('5.description')
            ->andReturn($newComment);
        $newComment = sprintf(
            '%s<p>%s<pre>%s</pre></p>',
            $newComment,
            $this->message,
            $this->attachments[1]['url']
        );
        $repMock->shouldNotReceive('setAttachment');
        $repMock->shouldReceive('set')
            ->once()
            ->with(5, $newComment);

        $expected = 2;
        $this->assertEquals(
            $expected,
            $this->mapper->map($this->attachments, 5, $repMock, [$this, 'attachmentCallback'])
        );
    }

    public function attachmentCallback($attachment)
    {
        $this->assertSame($this->attachments[$this->index], $attachment);
        $this->index++;
        return $attachment;
    }
}
