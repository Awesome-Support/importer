<?php

namespace Pressware\AwesomeSupport\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class SampleTest extends TestCase
{
    public function testAssertIsTrue()
    {
        $this->assertTrue(true);
    }

    protected function setUp()
    {
        parent::setUp();
//        Monkey\setUp();
    }

    protected function tearDown()
    {
//        Monkey\tearDown();
        parent::tearDown();
    }
}
