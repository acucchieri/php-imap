<?php

namespace AC\Imap\Tests;

use AC\Imap\Imap;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testConnect()
    {
        $imap = new Imap([
            'host' => $_ENV['IMAP_HOST'],
            'port' => $_ENV['IMAP_PORT'],
            'folder' => $_ENV['IMAP_FOLDER'],
            'user' => $_ENV['IMAP_USER'],
            'password' => $_ENV['IMAP_PASS'],
            'flags' => $_ENV['IMAP_FLAGS'],
            'lazy' => false,
        ]);

        $this->assertNotNull($imap->getStream());
    }
}
