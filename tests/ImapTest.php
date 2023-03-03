<?php

namespace AC\Imap\Tests;

use AC\Imap\Collection\MessageCollection;
use AC\Imap\Tests\TestCase\ImapTestCase;

class ImapTest extends ImapTestCase
{
    public function testCheck(): void
    {
        $expected = '}'.$_ENV['IMAP_FOLDER'];
        $result = self::$imap->check();

        $this->assertNotFalse($result);
        $this->assertStringEndsWith($expected, $result->Mailbox);
    }

    public function testAppend(): void
    {
        $message = sprintf('From: %s', self::$from)."\r\n"
            ."To: to@domain.tld\r\n"
            ."Subject: Test Append\r\n"
            ."\r\n"
            ."Test message.\r\n";

        $this->assertTrue(self::$imap->append($message));
    }

    /**
     * @depends testAppend
     */
    public function testSearch(): MessageCollection
    {
        $messages = self::$imap->search(sprintf('FROM "%s"', self::$from));

        $this->assertCount(1, $messages);

        return $messages;
    }

    /**
     * @depends testSearch
     */
    public function testExpunge(MessageCollection $messages): void
    {
        foreach ($messages as $message) {
            $message->markAsDeleted();
        }

        $this->assertTrue(self::$imap->expunge());

        $messages = self::$imap->search(sprintf('FROM "%s"', self::$from));
        $this->assertCount(0, $messages);
    }

    public function testListMailbox(): void
    {
        $mailboxes = self::$imap->listMailboxes();

        $this->assertContains(self::$imap->getMailbox(), $mailboxes);
    }

    public function testSwitchMailbox(): void
    {
        $originalMailbox = self::$imap->getMailbox();
        $newMailbox = self::$imap->formatMailbox('INBOX');

        // switch to INBOX
        $this->assertTrue(self::$imap->switchMailbox($newMailbox));
        $this->assertStringEndsWith('}INBOX', self::$imap->check()->Mailbox);

        // back to $_ENV['IMAP_FOLDER']
        $this->assertTrue(self::$imap->switchMailbox($originalMailbox));
        $this->assertStringEndsWith('}'.$_ENV['IMAP_FOLDER'], self::$imap->check()->Mailbox);
    }
}
