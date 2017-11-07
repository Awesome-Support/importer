<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI;

use Pressware\AwesomeSupport\API\Abstracts\GuzzleClient;

class HttpStub extends GuzzleClient
{
    public function getTickets()
    {
        // nothing needed here.
    }
}
