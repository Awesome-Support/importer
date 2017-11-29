<?php

namespace Pressware\AwesomeSupport\Tests\Importer;

use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\Importer\Locator;

class LocatorTest extends TestCase
{
    protected $locator;

    protected function setUp()
    {
        parent::setUp();

        $this->locator = new Locator([
            1 => [
                new AttachmentStub(501, 1, 'https://example.com/foo.jpg'),
                new AttachmentStub(502, 1, 'https://example.com/bar.jpg'),
                new AttachmentStub(503, 1, 'https://example.com/baz.jpg'),
            ],
        ]);
    }

    public function testHasExistingAttachments()
    {
        $this->assertTrue($this->locator->hasExistingAttachments(1));
        $this->assertFalse($this->locator->hasExistingAttachments(1000));
    }

    public function testFindAttachment()
    {
        $this->assertTrue($this->locator->findAttachment(1, ['filename' => 'baz.jpg']));
        $this->assertTrue($this->locator->findAttachment(1, ['filename' => 'bar.jpg']));
        $this->assertFalse($this->locator->findAttachment(1, ['filename' => 'foobar.jpg']));
    }
}
