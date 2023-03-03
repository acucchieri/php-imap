<?php

/*
 * This file is part of the php-imap package.
 *
 * (c) acucchieri <https://github.com/acucchieri>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AC\Imap;

/**
 * Message class.
 *
 * @author acucchieri <https://github.com/acucchieri>
 */
class Message
{
    public const FLAG_SEEN = '\\Seen';
    public const FLAG_ANSWERED = '\\Answered';
    public const FLAG_FLAGGED = '\\Flagged';
    public const FLAG_DELETED = '\\Deleted';
    public const FLAG_DRAFT = '\\Draft';

    private int $uid;
    private \IMAP\Connection $stream;
    private \stdClass $header;
    /** @var array|string[] */
    private array $body = [
        'plain' => '',
        'html' => '',
        'other' => '',
    ];
    /** @var array<array<string>> */
    private array $attachments = [];
    private bool $isBodyParsed = false;

    public function __construct(\IMAP\Connection $stream, int $uid)
    {
        $this->stream = $stream;
        $this->uid = $uid;
        $this->parseHeader();
    }

    /**
     * Gets the message sequence number.
     *
     * @return int
     */
    public function getNo(): int
    {
        return $this->msgno();
    }

    /**
     * Gets the message UID.
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * Gets the message date.
     *
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        if (isset($this->header->date)) {
            $date = $this->header->date;
        } elseif (isset($this->header->Date)) {
            $date = $this->header->Date;
        } elseif (isset($this->header->MailDate)) {
            $date = $this->header->MailDate;
        } elseif (isset($this->header->udate)) {
            $date = $this->header->udate;
        } else {
            throw new \Exception('Cannot retrieve message date.');
        }

        return new \DateTime($date);
    }

    /**
     * Gets the message subject.
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->mimeDecode($this->header->subject);
    }

    /**
     * Gets the body plain part.
     *
     * @return string The body plain part
     */
    public function getBodyPlain(): string
    {
        if (!$this->isBodyParsed) {
            $this->parseBody();
        }

        return $this->body['plain'];
    }

    /**
     * Gets the body html part.
     *
     * @return string The body html part
     */
    public function getBodyHtml(): string
    {
        if (!$this->isBodyParsed) {
            $this->parseBody();
        }

        return $this->body['html'];
    }

    /**
     * @return array<array<string>>
     */
    public function getAttachments(): array
    {
        if (!$this->isBodyParsed) {
            $this->parseBody();
        }

        return $this->attachments;
    }

    /**
     * @return array<string>
     */
    public function getFlags(): array
    {
        $flags = [];

        if (!$this->isMarkAsUnread()) {
            $flags[] = self::FLAG_SEEN;
        }
        if ($this->isMarkAsAnswered()) {
            $flags[] = self::FLAG_ANSWERED;
        }
        if ($this->isMarkAsImportant()) {
            $flags[] = self::FLAG_FLAGGED;
        }
        if ($this->isMarkAsDeleted()) {
            $flags[] = self::FLAG_DELETED;
        }
        if ($this->isMarkAsDraft()) {
            $flags[] = self::FLAG_DRAFT;
        }

        return $flags;
    }

    /**
     * Sets flags on message.
     *
     * @param array<string> $flags The flags
     *                             "\\Seen", "\\Answered", "\\Flagged", "\\Deleted" ou "\\Draft"
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function setFlags(array $flags): bool
    {
        $flag = implode(' ', $flags);
        $status = imap_setflag_full($this->stream, (string)$this->uid, $flag, ST_UID);
        $this->parseHeader();

        return $status;
    }

    /**
     * Clears flags on message.
     *
     * @param array<string> $flags Flags to remove. If empty, all flags will be deleted
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function clearFlags(array $flags = []): bool
    {
        if (empty($flags)) {
            $flags = $this->getFlags();
        }

        $flag = implode(' ', $flags);
        $status = imap_clearflag_full($this->stream, (string)$this->uid, $flag, ST_UID);
        $this->parseHeader();

        return $status;
    }

    /**
     * Move message to specified mailbox.
     *
     * @param string $mailbox The mailbox name
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function move(string $mailbox): bool
    {
        return imap_mail_move($this->stream, (string)$this->uid, $mailbox, CP_UID);
    }

    /**
     * Copy message to specified mailbox.
     *
     * @param string $mailbox The mailbox name
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function copy(string $mailbox): bool
    {
        return imap_mail_copy($this->stream, (string)$this->uid, $mailbox, CP_UID);
    }

    public function isMarkAsAnswered(): bool
    {
        return 'A' === $this->header->Answered;
    }

    public function isMarkAsDeleted(): bool
    {
        return 'D' === $this->header->Deleted;
    }

    public function isMarkAsDraft(): bool
    {
        return 'X' === $this->header->Draft;
    }

    public function isMarkAsImportant(): bool
    {
        return 'F' === $this->header->Flagged;
    }

    public function isMarkAsRecent(): bool
    {
        return 'R' === $this->header->Recent;
    }

    public function isMarkAsUnread(): bool
    {
        return 'N' === $this->header->Recent || 'U' === $this->header->Unseen;
    }

    public function markAsAnswered(): bool
    {
        return $this->setFlags([self::FLAG_ANSWERED]);
    }

    public function markAsUnanswered(): bool
    {
        return $this->clearFlags([self::FLAG_ANSWERED]);
    }

    public function markAsDeleted(): bool
    {
        return $this->setFlags([self::FLAG_DELETED]);
    }

    public function markAsUndeleted(): bool
    {
        return $this->clearFlags([self::FLAG_DELETED]);
    }

    public function markAsImportant(): bool
    {
        return $this->setFlags([self::FLAG_FLAGGED]);
    }

    public function markAsNormal(): bool
    {
        return $this->clearFlags([self::FLAG_FLAGGED]);
    }

    public function markAsRead(): bool
    {
        return $this->setFlags([self::FLAG_SEEN]);
    }

    public function markAsUnread(): bool
    {
        return $this->clearFlags([self::FLAG_SEEN]);
    }

    public function toString(): string
    {
        return imap_fetchheader($this->stream, $this->uid, FT_UID)
            ."\r\n"
            .imap_body($this->stream, $this->uid, FT_UID | FT_PEEK);
    }

    private function msgno(): int
    {
        return imap_msgno($this->stream, $this->uid);
    }

    private function parseBody(): void
    {
        $structure = imap_fetchstructure($this->stream, $this->uid, FT_UID);
        if ($structure) {
            if (isset($structure->parts) && !empty($structure->parts)) {
                foreach ($structure->parts as $key => $part) {
                    $section = $key + 1;
                    $this->parsePart($part, (string)$section);
                }
            } else {
                $this->parsePart($structure);
            }
        }

        $this->isBodyParsed = true;
    }

    private function parsePart(\stdClass $part, string $section = null): void
    {
        // nested parts
        if (isset($part->parts) && !empty($part->parts)) {
            foreach ($part->parts as $subSection => $subPart) {
                $notAttachment = (!isset($part->disposition) || 'attachment' !== $part->disposition);
                if (isset($part->type) && isset($part->subtype)
                    && 2 == $part->type
                    && 'RFC822' === $part->subtype
                    && $notAttachment
                ) {
                    $this->parsePart($subPart, $section);
                } else {
                    $this->parsePart($subPart, $section.'.'.($subSection + 1));
                }
            }

            return;
        }

        $parameters = [];
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

        if (false === $data) {
            throw new \Exception('Cannot read the message body');
        }

        // cf https://github.com/barbushin/php-imap/blob/master/src/PhpImap/Mailbox.php
        //      methode : protected function initMailPart
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
                $data = imap_base64($data); // base64 encoded data => original string
                if (false === $data) {
                    throw new \Exception('Cannot parse the message body');
                }
                break;
            case ENCQUOTEDPRINTABLE:    // 4
                $data = imap_qprint($data);   // quoted-printable string => 8 bit string
                if (false === $data) {
                    throw new \Exception('Cannot parse the message body');
                }
                break;
            case ENCOTHER:              // 5
                break;
        }

        switch ($part->type) {
            case TYPETEXT:          // 0 : text plain or html
                if (isset($parameters['charset']) && 'utf-8' !== strtolower($parameters['charset'])) {
                    if ('iso-8859-1' === strtolower($parameters['charset'])) {
                        $data = utf8_encode($data);
                    } else {
                        $data = iconv($parameters['charset'], "UTF-8//TRANSLIT//IGNORE", $data);
                        if (false === $data) {
                            throw new \Exception('Cannot convert the message body');
                        }
                    }
                }

                if ('plain' === strtolower($part->subtype)) {
                    $this->body['plain'] .= $data;
                } elseif ('html' === strtolower($part->subtype)) {
                    $this->body['html'] .= $data;
                } else {
                    if (isset($part->disposition) || 'attachment' !== $part->disposition) {
                        $filename = (isset($parameters['filename']))
                            ? $this->mimeDecode($parameters['filename'])
                            : ((isset($parameters['name']))
                                ? $this->mimeDecode($parameters['name'])
                                : uniqid((string)$section));
                        $this->attachments[] = [
                            'filename' => $filename,
                            'content' => $data,
                        ];
                    } else {
                        $this->body['other'] .= $data;
                    }
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
                $filename = isset($parameters['filename'])
                    ? $this->mimeDecode($parameters['filename'])
                    : (isset($parameters['name'])
                        ? $this->mimeDecode($parameters['name'])
                        : uniqid((string)$section));

                $this->attachments[] = [
                    'filename' => $filename,
                    'content' => $data,
                ];
                break;
        }
    }

    private function mimeDecode(string $original): string
    {
        $string = '';
        $elements = imap_mime_header_decode($original) ?: [];
        foreach ($elements as $element) {
            $string .= $element->text;
        }

        return $string;
    }

    private function parseHeader(): void
    {
        $header = imap_headerinfo($this->stream, $this->msgno());
        if (!$header) {
            throw new \Exception('Cannot read the header of the message "%d"', $this->msgno());
        }

        $this->header = $header;
    }
}
