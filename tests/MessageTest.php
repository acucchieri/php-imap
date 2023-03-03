<?php

namespace AC\Imap\Tests;

use AC\Imap\Message;
use AC\Imap\Tests\TestCase\ImapTestCase;

class MessageTest extends ImapTestCase
{
    public const SUBJECT = 'MessageTest';
    public const BODY_PLAIN = 'MessageTest body';

    private ?Message $message = null;

    protected function setUp(): void
    {
        $message = sprintf('From: %s', self::$from)."\r\n"
            ."To: to@domain.tld\r\n"
            .sprintf('Subject: %s', self::SUBJECT)."\r\n"
            ."\r\n"
            .self::BODY_PLAIN."\r\n";

        self::$imap->append($message);

        $messages = self::$imap->search(sprintf('FROM "%s"', self::$from));
        $this->message = $messages->first();
    }

    protected function tearDown(): void
    {
        $this->message->markAsDeleted();

        self::$imap->expunge();
    }

    public function testHeaders(): void
    {
        $this->assertIsInt($this->message->getNo());
        $this->assertIsInt($this->message->getUid());
        $this->assertTrue($this->message->getDate() instanceof \DateTime);
        $this->assertEquals(self::SUBJECT, $this->message->getSubject());
    }

    public function testFlags(): void
    {
        $this->message->markAsUnread();
        $this->assertTrue($this->message->isMarkAsUnread());
        $this->assertFalse($this->message->isMarkAsDraft());

        $this->message->markAsRead();
        $this->assertFalse($this->message->isMarkAsUnread());

        $this->message->markAsAnswered();
        $this->assertTrue($this->message->isMarkAsAnswered());

        $this->message->markAsImportant();
        $this->assertTrue($this->message->isMarkAsImportant());

        $this->message->markAsDeleted();
        $this->assertTrue($this->message->isMarkAsDeleted());

        $flags = $this->message->getFlags();
        $this->assertContains(Message::FLAG_ANSWERED, $flags);
        $this->assertContains(Message::FLAG_FLAGGED, $flags);
        $this->assertContains(Message::FLAG_DELETED, $flags);

        $this->message->markAsUndeleted();
        $this->assertFalse($this->message->isMarkAsDeleted());

        $this->message->markAsNormal();
        $this->assertFalse($this->message->isMarkAsImportant());

        $this->message->clearFlags();
        $this->assertCount(0, $this->message->getFlags());

        $this->message->setFlags([Message::FLAG_FLAGGED]);
        $this->assertCount(1, $this->message->getFlags());
    }

    public function testGetBodyPlain(): void
    {
        $this->assertEquals(self::BODY_PLAIN."\r\n", $this->message->getBodyPlain());
    }
}
