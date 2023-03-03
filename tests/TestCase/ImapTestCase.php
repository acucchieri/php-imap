<?php

namespace AC\Imap\Tests\TestCase;

use AC\Imap\Imap;
use PHPUnit\Framework\TestCase;

class ImapTestCase extends TestCase
{
    protected static ?Imap $imap;
    protected static ?string $from;

    public static function setUpBeforeClass(): void
    {
        self::$imap = new Imap([
            'host' => $_ENV['IMAP_HOST'],
            'port' => $_ENV['IMAP_PORT'],
            'folder' => $_ENV['IMAP_FOLDER'],
            'user' => $_ENV['IMAP_USER'],
            'password' => $_ENV['IMAP_PASS'],
            'flags' => $_ENV['IMAP_FLAGS'],
            'lazy' => $_ENV['IMAP_LAZY'],
        ]);

        self::$from = uniqid().'@domain.tld';
    }

    public static function tearDownAfterClass(): void
    {

    }
}
