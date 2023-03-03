<?php

namespace AC\Imap\Tests;

use AC\Imap\Collection\MessageCollection;
use AC\Imap\Message;
use AC\Imap\Tests\TestCase\ImapTestCase;

class MessageCollectionTest extends ImapTestCase
{
    public const FROM = 'message-collection-test@domain.tld';
    public const SUBJECT = 'MessageCollectionTest';
    public const BODY_PLAIN = 'MessageCollectionTest body';

    private ?MessageCollection $messages = null;

    protected function setUp(): void
    {
        foreach ([1, 2, 3] as $no) {
            $message = sprintf('From: %s', self::FROM)."\r\n"
                ."To: to@domain.tld\r\n"
                .sprintf('Subject: %s %d', self::SUBJECT, $no)."\r\n"
                ."\r\n"
                .self::BODY_PLAIN."\r\n";

            self::$imap->append($message);
        }

        $this->messages = self::$imap->search(sprintf('FROM "%s"', self::FROM));
    }

    protected function tearDown(): void
    {
        /** @var Message $message */
        foreach ($this->messages as $message) {
            $message->markAsDeleted();
        }

        self::$imap->expunge();
    }

    public function testArrayObject()
    {
        $firstMessage = $this->messages->first();
        $lastMessage = $this->messages->last();

        $this->assertEquals(self::SUBJECT.' 1', $firstMessage->getSubject());
        $this->assertEquals(self::SUBJECT.' 3', $lastMessage->getSubject());

        $this->messages->remove($lastMessage);
        $this->assertCount(2, $this->messages);

        $this->messages->add($lastMessage);
        $this->assertCount(3, $this->messages);

        $this->assertTrue(is_array($this->messages->toArray()));
    }

    public function testClear()
    {
        $messages = clone $this->messages;
        $messages->clear();

        $this->assertCount(0, $messages);
        $this->assertTrue($messages->isEmpty());
    }
}
