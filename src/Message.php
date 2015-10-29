<?php

namespace AC\Imap;


class Message
{
//    private $no;
    private $uid;
    private $stream;
    private $header;

    public function __construct($stream, $uid)
    {
        $this->stream = $stream;
//        $this->no = $no;
        $this->uid = $uid;
        $this->header = imap_headerinfo($this->stream, imap_msgno($this->stream, $this->uid));
    }

    public function getNo()
    {
        return imap_msgno($this->stream, $this->uid);
    }

    public function getUid()
    {
        return $this->uid;
    }

    public function getSubject()
    {
        $subject= '';
        $elements = imap_mime_header_decode($this->header->subject);
        foreach ($elements as $element) {
            $subject .= $element->text;
        }

        return $subject;
    }

    /**
     * Get the body plain part
     *
     * @return string The body plain part
     */
    public function getBodyPlain()
    {
        return imap_qprint(imap_fetchbody($this->stream, $this->uid, 1, FT_UID | FT_PEEK));
    }

    /**
     * Get the body html part
     *
     * @return string The body html part
     */
    public function getBodyHtml()
    {
        return imap_qprint(imap_fetchbody($this->stream, $this->uid, 2, FT_UID | FT_PEEK));
    }

    /**
     * Sets flags on message
     *
     * @param array $flags The flags
     * "\\Seen", "\\Answered", "\\Flagged", "\\Deleted" ou "\\Draft"
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function setFlags(array $flags)
    {
        $flag = implode(' ', $flags);
        $status =  imap_setflag_full($this->stream, $this->uid, $flag, ST_UID);
        $this->header = imap_headerinfo($this->stream, imap_msgno($this->stream, $this->uid));

        return $status;
    }

    /**
     * Clears flags on message
     *
     * @param array $flags The flags
     * "\\Seen", "\\Answered", "\\Flagged", "\\Deleted" ou "\\Draft"
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function clearFlags(array $flags)
    {
        $flag = implode(' ', $flags);
        $status =  imap_clearflag_full($this->stream, $this->uid, $flag, ST_UID);
        $this->header = imap_headerinfo($this->stream, imap_msgno($this->stream, $this->uid));

        return $status;
    }

    /**
     * Move message to specified mailbox
     *
     * @param string $mailbox The mailbox name
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function move($mailbox)
    {
        return imap_mail_move($this->stream, (string)$this->uid, $mailbox, CP_UID);
    }

    /**
     * Copy message to specified mailbox
     *
     * @param string $mailbox The mailbox name
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function copy($mailbox)
    {
        return imap_mail_copy($this->stream, (string)$this->uid, $mailbox, CP_UID);
    }


    public function isMarkAsAnswered()
    {
        return $this->header->Answered === 'A';
    }

    public function isMarkAsDeleted()
    {
        return $this->header->Deleted === 'D';
    }

    public function isMarkAsDraft()
    {
        return $this->header->Draft === 'X';
    }

    public function isMarkAsImportant()
    {
        return $this->header->Flagged === 'F';
    }

    public function isMarkAsRecent()
    {
        return $this->header->Recent === 'R';
    }

    public function isMarkAsUnread()
    {
        return $this->header->Recent === 'N' || $this->header->Unseen === 'U';
    }

    public function markAsAnswered()
    {
        return $this->setFlags(array('\\Answered'));
    }

    public function markAsUnanswered()
    {
        return $this->clearFlags(array('\\Answered'));
    }

    public function markAsDeleted()
    {
        return $this->setFlags(array('\\Deleted'));
    }

    public function markAsUndeleted()
    {
        return $this->clearFlags(array('\\Deleted'));
    }

    public function markAsImportant()
    {
        return $this->setFlags(array('\\Flagged'));
    }

    public function markAsNormal()
    {
        return $this->clearFlags(array('\\Flagged'));
    }

    public function markAsRead()
    {
        return $this->setFlags(array('\\Seen'));
    }

    public function markAsUnread()
    {
        return $this->clearFlags(array('\\Seen'));
    }
}
