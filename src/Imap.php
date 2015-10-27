<?php

namespace AC\Imap;

use AC\Imap\Message;
use AC\Imap\Collection\MessageCollection;

class Imap
{
    private $config;
    private $stream;
    private $mailbox;

    public function __construct($config)
    {
        $this->config = array_merge(array(
            'host' => null,
            'port' => 143,
            'folder' => 'INBOX',
            'user' => null,
            'password' => null,
            'flags' => array(),
        ), $config);

        $this->connect();
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function getMailbox()
    {
        return $this->mailbox;
    }

    /**
     * Returns a collection of messages matching the given search criteria
     * @link http://php.net/manual/en/function.imap-search.php
     *
     * @param string $criteria A string, delimited by spaces,
     * in which the following keywords are allowed.
     * Any multi-word arguments (e.g. FROM "joey smith") must be quoted.
     * Results will match all criteria entries.
     * ALL - return all messages matching the rest of the criteria
     * ANSWERED - match messages with the \\ANSWERED flag set
     * BCC "string" - match messages with "string" in the Bcc: field
     * BEFORE "date" - match messages with Date: before "date"
     * BODY "string" - match messages with "string" in the body of the message
     * CC "string" - match messages with "string" in the Cc: field
     * DELETED - match deleted messages
     * FLAGGED - match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
     * FROM "string" - match messages with "string" in the From: field
     * KEYWORD "string" - match messages with "string" as a keyword
     * NEW - match new messages
     * OLD - match old messages
     * ON "date" - match messages with Date: matching "date"
     * RECENT - match messages with the \\RECENT flag set
     * SEEN - match messages that have been read (the \\SEEN flag is set)
     * SINCE "date" - match messages with Date: after "date"
     * SUBJECT "string" - match messages with "string" in the Subject:
     * TEXT "string" - match messages with text "string"
     * TO "string" - match messages with "string" in the To:
     * UNANSWERED - match messages that have not been answered
     * UNDELETED - match messages that are not deleted
     * UNFLAGGED - match messages that are not flagged
     * UNKEYWORD "string" - match messages that do not have the keyword "string"
     * UNSEEN - match messages which have not been read yet
     * @return MessageCollection messages collection
     */
    public function search($criteria)
    {
        $messages = new MessageCollection();

        if ($results = imap_search($this->stream, $criteria)) {
            foreach ($results as $result) {
                $messages->append(new Message($this->stream, $result));
            }
        }

        return $messages;
    }

    /**
     * Append a string message to a specified mailbox
     *
     * @param string $message The message to be append, as a string
     * @param string $mailbox The mailbox name
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function append($message, $mailbox = null)
    {
        if (!$mailbox) {
            $mailbox =  $this->mailbox;
        }

        return imap_append($this->stream, $mailbox, $message);
    }

    /**
     * Check current mailbox
     * @link http://php.net/manual/en/function.imap-check.php
     *
     * @return object  Returns the information in an object
     */
    public function check()
    {
        return imap_check($this->stream);
    }

    /**
     * Delete all messages marked for deletion
     *
     * @return bool TRUE
     */
    public function expunge()
    {
        return imap_expunge($this->stream);
    }

    private function connect()
    {
        $this->mailbox = sprintf("{%s:%s%s}%s",
            $this->config['host'],
            $this->config['port'],
            ($this->config['flags']) ? '/'.implode('/', $this->config['flags']) : null,
            $this->config['folder']
        );

        if (!$this->stream = imap_open($this->mailbox, $this->config['user'], $this->config['password'])) {
            throw new \Exception(sprintf('cannot connect to mailbox %s', $this->mailbox));
        }
    }


    /* A coder */
/*
    public function copyMessages();

    public function moveMessages();
*/
}
