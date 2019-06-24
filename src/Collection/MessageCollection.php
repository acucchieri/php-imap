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
 * @author acucchieri <https://github.com/acucchieri>
 */
class MessageCollection extends \ArrayObject
{
    public function __construct(array $array = array())
    {
        parent::__construct($array);
    }

    /**
     * Set the internal pointer to the first element and returns it.
     *
     * @return Message The first message of the collection
     */
    public function first()
    {
        return reset($this);
    }

    /**
     * Set the internal pointer to the last element and returns it.
     *
     * @return Message The last message of the collection
     */
    public function last()
    {
        return end($this);
    }

    /**
     * Add a Message at the end of the collection.
     *
     * @param Message $message The message
     *
     * @return MessageCollection
     */
    public function add(Message $message)
    {
        $this->append($message);

        return $this;
    }

    /**
     * Removes the Message from the collection.
     *
     * @param Message $message The message
     *
     * @return MessageCollection|false or FALSE if Message not found in the collection
     */
    public function remove(Message $message)
    {
        $key = array_search($message, $this->toArray(), false);
        if (false === $key) {
            return false;
        }
        $this->offsetUnset($key);

        return $this;
    }

    public function clear()
    {
        $this->exchangeArray(array());

        return $this;
    }

    /**
     * Determine whether the collection is empty.
     *
     * @return bool TRUE if the collection is empty, FALSE otherwise
     */
    public function isEmpty()
    {
        return 0 === $this->count();
    }

    /**
     * Return a php array of Messages
     * Alias of \ArrayObject::getArrayCopy().
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getArrayCopy();
    }
}
