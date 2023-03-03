<?php

use AC\Imap\Imap;

$imap = new Imap([
    'host' => 'imap-server.domain.tld', // Hostname. Required
    'port' => 143,                      // Host port. Default : 143
    'folder' => 'INBOX',                // Mailbox name. Default : 'INBOX'
    'user' => 'user-login',             // Login. Required
    'password' => 'user-password',      // Password. Required
    'flags' => [],                      // Connection flags. Optionnal
    'lazy' => false,                    // Lazy mode. Default : false
]);

/** @var \AC\Imap\Collection\MessageCollection $result */
$result = $imap->search('FROM "foo@bar.tld"');

/** @var \AC\Imap\Message $message */
foreach ($result as $message) {
    var_dump($message->getSubject());
}
