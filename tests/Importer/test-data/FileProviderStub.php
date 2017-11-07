<?php

namespace Pressware\AwesomeSupport\Tests\Importer;

use Pressware\AwesomeSupport\API\Contracts\ApiInterface;

class FileProviderStub implements ApiInterface
{
    public function getTickets()
    {
        return require __DIR__ . '/data-structure.php';
    }
}
