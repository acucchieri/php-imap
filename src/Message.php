<?php

namespace AC\Imap;


class Message
{
    private $uid;
    private $stream;
    private $header;
    private $body = array(
        'plain' => '',
        'html'  => '',
        'other' => '',
    );
    private $attachments = array();
    private $isBodyParsed = false;

    public function __construct($stream, $uid)
    {
        $this->stream = $stream;
        $this->uid = $uid;
        $this->header = imap_headerinfo($this->stream, $this->msgno());
    }

    /**
     * Gets the message sequence number
     *
     * @return type
     */
    public function getNo()
    {
        return $this->msgno();
    }

    /**
     * Gets the message UID
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Gets the message date
     *
     * @return int
     */
    public function getDate()
    {
        return new \DateTime($this->header->date);
    }

    /**
     * Gets the message subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->mimeDecode($this->header->subject);
    }

    /**
     * Gets the body plain part
     *
     * @return string The body plain part
     */
    public function getBodyPlain()
    {
        if (!$this->isBodyParsed) {
            $this->parseBody();
        }

        return $this->body['plain'];
    }

    /**
     * Gets the body html part
     *
     * @return string The body html part
     */
    public function getBodyHtml()
    {
        if (!$this->isBodyParsed) {
            $this->parseBody();
        }

        return $this->body['html'];
    }

    /**
     *
     * @return array
     */
    public function getAttachments()
    {
        if (!$this->isBodyParsed) {
            $this->parseBody();
        }

        return $this->attachments;
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
        $this->header = imap_headerinfo($this->stream, $this->msgno());

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
        $this->header = imap_headerinfo($this->stream, $this->msgno());

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


    public function toString()
    {
        return  imap_fetchheader($this->stream, $this->uid, FT_UID)
            . "\r\n"
            . imap_body($this->stream, $this->uid, FT_UID | FT_PEEK)
        ;
    }

    private function msgno()
    {
        return imap_msgno($this->stream, $this->uid);
    }

    private function parseBody()
    {
        $structure = imap_fetchstructure($this->stream, $this->uid, FT_UID);
        if(isset($structure->parts) && !empty($structure->parts)) {
            foreach($structure->parts as $key => $part) {
                $this->parsePart($part, $key + 1);
            }
        }
        else {
            $this->parsePart($structure);
        }




        $this->isBodyParsed = true;
    }

    private function parsePart($part, $section = null)
    {
        // nested parts
        if(isset($part->parts) && !empty($part->parts)) {
            foreach($part->parts as $subSection => $subPart) {
                if (2 == $part->type
                    && 'RFC822' === $part->subtype
                    && (!isset($part->disposition) || $part->disposition !== "attachment")
                ) {
                    $this->parsePart($subPart, $section);
                } else {
                    $this->parsePart($subPart, $section . '.' . ($subSection + 1));
                }
            }

            return;
        }

        $parameters = array();
        if ($part->ifparameters) {
            foreach ($part->parameters as $row) {
                $parameters[strtolower($row->attribute)] = $row->value;
            }

        }
        if ($part->ifdparameters) {
            foreach ($part->dparameters as $row) {
                $parameters[strtolower($row->attribute)] = $row->value;
            }

        }

        if ($section) {
            $data = imap_fetchbody($this->stream, $this->uid, $section, FT_UID | FT_PEEK);
        } else {
            $data = imap_body($this->stream, $this->uid, FT_UID | FT_PEEK);
        }

        // cf https://github.com/barbushin/php-imap/blob/master/src/PhpImap/Mailbox.php
        //      methode : protected function initMailPart


        // Nomaliser data ( => quoted print machin)
        // mapper data vers bodyplain, bodyhtml, attachments
        // attention :  - penser a concatener bodyplain et bodyhtml
        //              - penser a decoder (imap_qprint) puis convertir en utf-8 si besoin (utf8_encode)


        switch ($part->encoding) {
            case ENC7BIT:               // 0
                break;
            case ENC8BIT:               // 1
                // $data = imap_utf8($data);   // A MIME encoded string => UTF-8 encoded string
                break;
            case ENCBINARY:             // 2
                // $data = imap_binary($data); // 8bit string => base64 string
                break;
            case ENCBASE64:             // 3
                // $data = preg_replace('~[^a-zA-Z0-9+=/]+~s', '', $data); // https://github.com/barbushin/php-imap/issues/88
                $data = imap_base64($data); // base64 encoded data => original string
                break;
            case ENCQUOTEDPRINTABLE:    // 4
                $data = imap_qprint($data);   // quoted-printable string => 8 bit string
                break;
            case ENCOTHER:              // 5
                break;
        }


        switch($part->type) {
            case TYPETEXT:          // 0 : text plain or html
                if (isset($parameters['charset']) &&  $parameters['charset'] == 'ISO-8859-1' ) {
                    if ('iso-8859-1' === strtolower($parameters['charset'])) {
                        $data = utf8_encode($data);
                    }
                }
                if ('plain' === strtolower($part->subtype)) {
                    $this->body['plain'] .= $data;
                } elseif ('html' === strtolower($part->subtype)) {
                    $this->body['html'] .= $data;
                } else {
                    $this->body['other'] .= $data;
                }
                break;
            case TYPEMULTIPART:     // 1 : multipart header
            case TYPEMESSAGE:       // 2 : message attachment header
                break;
            case TYPEAPPLICATION:   // 3 : application
            case TYPEAUDIO:         // 4 : audio
            case TYPEIMAGE:         // 5 : image
            case TYPEVIDEO:         // 6 : video
            case TYPEMODEL:         // 7 : model
            case TYPEOTHER:         // 8 : other (unkhnow)
                $filename = (isset($parameters['filename']))
                        ? $this->mimeDecode($parameters['filename'])
                        : (isset($parameters['name']))
                            ? $this->mimeDecode($parameters['name'])
                            : uniqid((string)$section);

                $this->attachments[] = array(
                    'filename' => $filename,
                    'content' => $data,
                );
                break;
        }
    }

    private function mimeDecode($original)
    {
        $string = '';
        $elements = imap_mime_header_decode($original);
        foreach ($elements as $element) {
            $string .= $element->text;
        }

        return $string;
    }
}
