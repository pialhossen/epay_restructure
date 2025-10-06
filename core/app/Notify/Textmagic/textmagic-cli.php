<?php

require __DIR__."\Services\TextmagicRestClient.php";

use App\Notify\Textmagic\Services\RestException;
use App\Notify\Textmagic\Services\TextmagicRestClient;

define('VERSION', '0.01');

/**
 * Client object
 */
$client = new TextmagicRestClient('<USERNAME>', '<APIV2_TOKEN>');

/**
 * User object
 */
$user = false;

/**
 * Pagination
 */
$page = 1;
$limit = 10;
$paginatedFunction = 'exitOk';

/**
 * sendMessage containers
 */
$sendingContacts = [];
$sendingLists = [];

/**
 * Default "Back to main menu" link
 */
$backMenu = [
    'Back to main menu' => 'showMainMenu',
];

/**
 *  Show main menu
 */
function showMainMenu()
{
    flushPagination();

    $items = [
        'Contacts' => 'showAllContacts',
        'Lists' => 'showAllLists',
        'Messages' => 'showMessagesMenu',
        'Templates' => 'showAllTemplates',
        'Information' => 'showInformation',
    ];

    showMenu($items);
}

/**
 *  Show messages menu
 */
function showMessagesMenu()
{
    global $backMenu;

    $items = [
        'Show outgoing messages' => 'showMessagesOut',
        'Show incoming messages' => 'showMessagesIn',
        'Send message' => 'sendMessage',
    ];

    showMenu($items + $backMenu);
}

/**
 *  Show base account information
 */
function showInformation()
{
    global $user, $backMenu;

    echo <<<EOT

ACCOUNT INFORMATION
===================

ID          : {$user['id']}
Username    : {$user['username']}
First Name  : {$user['firstName']}
Last Name   : {$user['lastName']}
Balance     : {$user['balance']} {$user['currency']['id']}
Timezone    : {$user['timezone']['timezone']} ({$user['timezone']['offset']})

EOT;

    showMenu($backMenu);
}

/**
 *  Show all user contacts (including shared)
 */
function showAllContacts()
{
    global $client, $page, $limit, $paginatedFunction, $backMenu;

    $paginatedFunction = 'showAllContacts';

    try {
        $response = $client->contacts->getList(
            [
                'page' => $page,
                'limit' => $limit,
                'shared' => true,
            ]
        );
    } catch (\ErrorException $e) {
        error($e);
    }

    $contacts = $response['resources'];

    echo <<<EOT

ALL CONTACTS
============
Page {$response['page']} of {$response['pageCount']}

EOT;

    foreach ($contacts as $contact) {
        echo "{$contact['id']}. {$contact['firstName']} {$contact['lastName']}, {$contact['phone']}\n";
    }

    $items = [
        'Previous page' => 'goToPreviousPage',
        'Next page' => 'goToNextPage',
        'Show contact details' => 'showContact',
        'Delete contact' => 'deleteContact',
    ];

    showMenu($items + $backMenu);
}

/**
 *  Show one contact details
 */
function showContact()
{
    global $client;

    $id = readNumber('Enter contact ID');

    if (! $id) {
        return showAllContacts();
    }

    try {
        $contact = $client->contacts->get($id);
    } catch (\ErrorException $e) {
        error($e);
    }

    echo <<<EOT

CONTACT INFORMATION
===================

Name    : {$contact['firstName']} {$contact['lastName']}
Phone   : +{$contact['phone']} ({$contact['country']['name']})
Company : {$contact['companyName']}

EOT;

    return showAllContacts();
}

/**
 *  Delete contact permanently
 */
function deleteContact()
{
    global $client;

    $id = readNumber('Enter contact ID');

    if (! $id) {
        return showAllContacts();
    }

    try {
        $client->contacts->delete($id);
    } catch (\ErrorException $e) {
        error($e);
    }

    echo "\nContact deleted successfully\n";

    return showAllContacts();
}

/**
 *  Show all user lists (including shared)
 */
function showAllLists()
{
    global $client, $page, $limit, $paginatedFunction, $backMenu;

    $paginatedFunction = 'showAllLists';

    try {
        $response = $client->lists->getList(
            [
                'page' => $page,
                'limit' => $limit,
                'shared' => true,
            ]
        );
    } catch (\ErrorException $e) {
        error($e);
    }

    $lists = $response['resources'];

    echo <<<EOT

ALL LISTS
=========
Page {$response['page']} of {$response['pageCount']}

EOT;

    foreach ($lists as $list) {
        echo "{$list['id']}. {$list['name']} ({$list['description']})\n";
    }

    $items = [
        'Previous page' => 'goToPreviousPage',
        'Next page' => 'goToNextPage',
    ];

    showMenu($items + $backMenu);
}

/**
 *  Show all sent messages
 */
function showMessagesOut()
{
    global $client, $page, $limit, $paginatedFunction, $backMenu;

    $paginatedFunction = 'showMessagesOut';

    try {
        $response = $client->messages->getList(
            [
                'page' => $page,
                'limit' => $limit,
            ]
        );
    } catch (\ErrorException $e) {
        error($e);
    }

    $messages = $response['resources'];

    echo <<<EOT

SENT MESSAGES
=========
Page {$response['page']} of {$response['pageCount']}

EOT;

    foreach ($messages as $message) {
        echo "{$message['id']}. {$message['text']} (from {$message['receiver']})\n";
    }

    $items = [
        'Previous page' => 'goToPreviousPage',
        'Next page' => 'goToNextPage',
        'Delete message' => 'deleteMessageOut',
    ];

    showMenu($items + $backMenu);
}

/**
 *  Delete one sent message
 */
function deleteMessageOut()
{
    global $client;

    $id = readNumber('Enter message ID');

    if (! $id) {
        return showMessagesOut();
    }

    try {
        $client->messages->delete($id);
    } catch (\ErrorException $e) {
        error($e);
    }

    echo "\nMessage deleted successfully\n";

    return showMessagesOut();
}

/**
 *  Show all received messages
 */
function showMessagesIn()
{
    global $client, $page, $limit, $paginatedFunction, $backMenu;

    $paginatedFunction = 'showMessagesIn';

    try {
        $response = $client->replies->getList(
            [
                'page' => $page,
                'limit' => $limit,
            ]
        );
    } catch (\ErrorException $e) {
        error($e);
    }

    $replies = $response['resources'];

    echo <<<EOT

RECEIVED MESSAGES
=========
Page {$response['page']} of {$response['pageCount']}

EOT;

    foreach ($replies as $message) {
        echo "{$message['id']}. {$message['text']} (from {$message['sender']})\n";
    }

    $items = [
        'Previous page' => 'goToPreviousPage',
        'Next page' => 'goToNextPage',
        'Delete message' => 'deleteMessageIn',
    ];

    showMenu($items + $backMenu);
}

/**
 *  Delete one received message
 */
function deleteMessageIn()
{
    global $client;

    $id = readNumber('Enter message ID');

    if (! $id) {
        return showMessagesIn();
    }

    try {
        $client->replies->delete($id);
    } catch (\ErrorException $e) {
        error($e);
    }

    echo "\nMessage deleted successfully\n";

    return showMessagesIn();
}

/**
 *  Show all message templates
 */
function showAllTemplates()
{
    global $client, $page, $limit, $paginatedFunction, $backMenu;

    $paginatedFunction = 'showAllTemplates';

    try {
        $response = $client->templates->getList(
            [
                'page' => $page,
                'limit' => $limit,
            ]
        );
    } catch (\ErrorException $e) {
        error($e);
    }

    $templates = $response['resources'];

    echo <<<EOT

TEMPLATES
=========
Page {$response['page']} of {$response['pageCount']}

EOT;

    foreach ($templates as $template) {
        echo "{$template['id']}. {$template['name']}: {$template['content']}\n";
    }

    $items = [
        'Previous page' => 'goToPreviousPage',
        'Next page' => 'goToNextPage',
        'Delete template' => 'deleteTemplate',
    ];

    showMenu($items + $backMenu);
}

/**
 *  Delete one message template
 */
function deleteTemplate()
{
    global $client;

    $id = readNumber('Enter template ID');

    if (! $id) {
        return showAllTemplates();
    }

    try {
        $client->templates->delete($id);
    } catch (\ErrorException $e) {
        error($e);
    }

    echo "\nTemplate deleted successfully\n";

    return showAllTemplates();
}

/**
 *  Send outgoing message to phones, contacts and/or contact lists
 */
function sendMessage()
{
    global $client;

    echo <<<'EOT'

SEND MESSAGE
============

EOT;
    echo 'Text: ';
    $sendingText = trim(fgets(STDIN));
    echo "\n\n";

    echo "Enter phone numbers, separated by [ENTER]. Empty string to break.\n";

    $sendingPhones = [];
    $sendingContacts = [];
    $sendingLists = [];

    do {
        echo "\nPhone: ";
        $phone = trim(fgets(STDIN));
        array_push($sendingPhones, $phone);
    } while ($phone);
    array_pop($sendingPhones);

    echo "\n\nEnter contact IDs, separated by [ENTER]. Empty string to break.\n";

    do {
        $contact = readNumber('Contact');
        array_push($sendingContacts, $contact);
    } while ($contact);
    array_pop($sendingContacts);

    echo "\n\nEnter list IDs, separated by [ENTER]. Empty string to break.\n";

    do {
        $list = readNumber('List');
        array_push($sendingLists, $list);
    } while ($list);
    array_pop($sendingLists);

    $sendingPhones = implode(', ', $sendingPhones);
    $sendingContacts = implode(', ', $sendingContacts);
    $sendingLists = implode(', ', $sendingLists);

    echo "\n\nYOU ARE ABOUT TO SEND MESSAGES TO:".
          ($sendingPhones ? "\nPhone numbers: ".$sendingPhones : '').
          ($sendingContacts ? "\nContacts: ".$sendingContacts : '').
          ($sendingLists ? "\nLists: ".$sendingLists : '');
    echo "\nAre you sure (y/n)? ";

    $answer = strtolower(trim(fgets(STDIN)));
    if ($answer != 'y') {
        return showMainMenu();
    }

    try {
        $result = $client->messages->create(
            [
                'text' => $sendingText,
                'phones' => $sendingPhones,
                'contacts' => $sendingContacts,
                'lists' => $sendingLists,
            ]
        );
    } catch (\ErrorException $e) {
        error($e);
    }

    echo "\nMessage {$result['id']} sent\n";

    return showMainMenu();
}

/**
 *  Error handler
 */
function error($e)
{
    if ($e instanceof RestException) {
        echo '[ERROR] '.$e->getMessage()."\n";
        foreach ($e->getErrors() as $key => $value) {
            echo '['.$key.'] '.$value."\n";
        }
    } else {
        echo '[ERROR] '.$e->getMessage()."\n";
    }

    exit(0);
}

/**
 *  Show top user banner
 */
function showUserInfo()
{
    global $user;

    echo 'TextMagic CLI v'.VERSION." || {$user['firstName']}  {$user['lastName']} ({$user['username']}) || {$user['balance']} {$user['currency']['id']}\n";
}

/**
 *  Show numered menu and return user choice
 */
function showMenu($itemsRef)
{
    $functionsRef = [];
    echo "\n";

    $i = 0;
    foreach ($itemsRef as $key => $value) {
        $i++;
        echo $i.' '.$key."\n";
        $functionsRef[$i] = $value;
    }

    $i++;
    echo $i." Exit\n";
    $functionsRef[$i] = 'exitOk';

    $choice = readNumber("Your choice ($i)");

    if (! $choice || ! isset($functionsRef[$choice])) {
        $function = $functionsRef[$i];
    } else {
        $function = $functionsRef[$choice];
    }

    $function();
}

/**
 *  Go to previous page when browsing paginated resource
 */
function goToPreviousPage()
{
    global $page, $paginatedFunction;

    if ($page <= 2) {
        $page = 1;
    } else {
        $page--;
    }

    $paginatedFunction();
}

/**
 *  Go to next page when browsing paginated resource
 */
function goToNextPage()
{
    global $page, $paginatedFunction;

    $page++;

    $paginatedFunction();
}

/**
 *  Reset current page, limit and paginated resource fetch function
 */
function flushPagination()
{
    global $page, $limit, $paginatedFunction;

    $page = 1;
    $limit = 10;
    $paginatedFunction = 'exitOk';
}

/**
 *  Normal program termination
 */
function exitOk()
{
    echo "\nBye!\n";
    exit(0);
}

/**
 *  Read number value
 */
function readNumber($text)
{
    echo "\n$text: ";
    $choice = intval(trim(fgets(STDIN)));

    return $choice;
}

/**
 *  Main program procedure
 */
function main()
{
    global $client, $user;

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        procSystem('cls');
    } else {
        procSystem('clear');
    }

    try {
        $user = $client->user->get();
    } catch (\ErrorException $e) {
        error($e);
    }

    showUserInfo();
    showMainMenu();
}

/**
 *  System function handler
 */
function procSystem($cmd)
{
    $pp = proc_open($cmd, [STDIN, STDOUT, STDERR], $pipes);
    if (! $pp) {
        return 127;
    }

    return proc_close($pp);
}

main();
