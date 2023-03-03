<?php

/*
 * This file is part of the php-imap package.
 *
 * (c) acucchieri <https://github.com/acucchieri>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AC\Imap\Collection;

use AC\Imap\Message;

/**
 * Messages Collection.
 *
 * @extends \ArrayObject<int, Message>
 *
 * @author acucchieri <https://github.com/acucchieri>
 */
class MessageCollection extends \ArrayObject
{
    /**
     * @param array<Message> $array
     */
    public function __construct(array $array = [])
    {
        parent::__construct($array);
    }

    /**
     * Set the internal pointer to the first element and returns it.
     *
     * @return Message|false The first message of the collection
     */
    public function first(): Message|false
    {
        if (!$this->count()) {
            return false;
        }

        $iterator = new \ArrayIterator($this->toArray());
        $iterator->rewind();

        $a = $iterator->current();

        return $iterator->current();
    }

    /**
     * Set the internal pointer to the last element and returns it.
     *
     * @return Message|false The last message of the collection
     */
    public function last(): Message|false
    {
        if (!$this->count()) {
            return false;
        }

        return $this[$this->count()-1] ?: false;
    }

    /**
     * Add a Message at the end of the collection.
     *
     * @param Message $message The message
     *
     * @return MessageCollection
     */
    public function add(Message $message): MessageCollection
    {
        $this->append($message);

        return $this;
    }

    /**
     * Removes the Message from the collection.
     *
     * @param Message $message The message
     *
     * @return MessageCollection|null or FALSE if Message not found in the collection
     */
    public function remove(Message $message): MessageCollection|null
    {
        $key = array_search($message, $this->toArray());
        if (false === $key) {
            return null;
        }
        $this->offsetUnset((int)$key);

        return $this;
    }

    public function clear(): MessageCollection
    {
        $this->exchangeArray([]);

        return $this;
    }

    /**
     * Determine whether the collection is empty.
     *
     * @return bool TRUE if the collection is empty, FALSE otherwise
     */
    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    /**
     * Return a php array of Messages
     * Alias of \ArrayObject::getArrayCopy().
     *
     * @return array<Message>
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }
}
