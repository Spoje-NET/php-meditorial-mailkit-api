![Packaging: deb](https://img.shields.io/badge/packaging-.deb-red?logo=debian&logoColor=white)


Spoje-NET/MailkitApi
======

PHP client library for the [Mailkit](https://www.mailkit.com/) email marketing platform API.

Supports both XML-RPC and JSON API interfaces with a typed, object-oriented API.

Requirements
------------

- PHP >= 8.1
- [ext-xmlrpc](https://www.php.net/manual/en/book.xmlrpc.php) — required for XML-RPC communication
- [nette/utils](https://github.com/nette/utils) ^4.0 — for JSON handling and string utilities

> **Note:** On PHP 8.0+, `ext-xmlrpc` has been moved to PECL and must be installed separately
> (e.g. `pecl install xmlrpc` or via your distribution's package manager).

Installation
------------

The best way to install is using [Composer](http://getcomposer.org/):

```sh
composer require meditorial/mailkit-api
```

Configuration
-------------

You need your **client ID** and **client MD5 hash** from your Mailkit account. These are used to
authenticate all API requests.

Your server's IP address must be **whitelisted** in the Mailkit admin panel for API calls to succeed.

The library communicates with:
- **XML-RPC**: `https://api.mailkit.eu/rpc.fcgi`
- **JSON**: `https://api.mailkit.eu/json.fcgi`

Usage
-----

### Initialization

The `MailkitApi` class is the main entry point. It requires four manager instances, each created with
a shared RPC client:

```php
use Igloonet\MailkitApi\RPC\Client;
use Igloonet\MailkitApi\Managers\MailingListsManager;
use Igloonet\MailkitApi\Managers\MessagesManager;
use Igloonet\MailkitApi\Managers\UsersManager;
use Igloonet\MailkitApi\Managers\WebHooksManager;
use Igloonet\MailkitApi\MailkitApi;

$client = new Client('your_client_id', 'your_client_md5');
$enabledLanguages = ['cs', 'en', 'de'];
$defaultLanguage = 'cs';

$api = new MailkitApi(
    new MailingListsManager($client, $enabledLanguages, $defaultLanguage),
    new UsersManager($client, $enabledLanguages, $defaultLanguage),
    new MessagesManager($client, $enabledLanguages, $defaultLanguage),
    new WebHooksManager(),
);
```

### Mailing Lists

```php
// List all mailing lists
$mailingLists = $api->getMailingListsManager()->getMailingLists();

foreach ($mailingLists as $list) {
    echo $list->getId() . ': ' . $list->getName() . ' (' . $list->getStatus()->value . ')' . PHP_EOL;
}

// Create a new mailing list
$newList = $api->getMailingListsManager()->createMailingList('Newsletter', 'Monthly newsletter');
echo 'Created list ID: ' . $newList->getId();

// Find by name
$list = $api->getMailingListsManager()->getMailingListByName('Newsletter');

// Delete (remove all subscribers and the list itself)
$api->getMailingListsManager()->deleteMailingList($listId);

// Flush (remove all subscribers but keep the list)
$api->getMailingListsManager()->flushMailingList($listId);
```

### User / Subscriber Management

```php
use Igloonet\MailkitApi\DataObjects\User;
use Igloonet\MailkitApi\DataObjects\Enums\Gender;

// Get users by email address
$users = $api->getUsersManager()->getUsersByEmailAddress('user@example.com');

// Get a single user by email ID
$user = $api->getUsersManager()->getUserByEmailId(12345);
echo $user->getEmail() . ' — status: ' . $user->getStatus()->value;

// Add a new subscriber
$user = new User('newuser@example.com');
$user->setFirstName('John');
$user->setLastName('Doe');
$user->setGender(Gender::MALE);
$user->setCompany('Acme Corp');
$user->setCustomField(1, 'custom value');

$api->getUsersManager()->addUser($user, $mailingListId, doubleOptIn: true);

// Edit existing user
$user->setLastName('Smith');
$api->getUsersManager()->editUser($user, $mailingListId, keepValues: true);

// Unsubscribe
$api->getUsersManager()->unsubscribeEmailAddress('user@example.com', sendOptOut: true);
// or by email ID
$api->getUsersManager()->unsubscribeEmailId(12345, sendOptOut: false);

// Revalidate a previously unsubscribed user
$api->getUsersManager()->revalidateEmailAddress(
    'user@example.com',
    agreement: true,
    channel: 'web',
    language: 'cs',
);

// Delete
$deletedIds = $api->getUsersManager()->deleteEmailAddress('user@example.com');
$api->getUsersManager()->deleteEmailId(12345);
```

### Sending Messages

```php
use Igloonet\MailkitApi\DataObjects\Message;
use Igloonet\MailkitApi\DataObjects\User;
use Igloonet\MailkitApi\DataObjects\Attachment;

$user = new User('recipient@example.com');
$user->setFirstName('Jane');
$user->setMailingListId($mailingListId);

$message = new Message($user);
$message->setSubject('Hello!');
$message->setBody('<h1>Welcome</h1><p>Thank you for subscribing.</p>');

// Template variables (merged into the Mailkit campaign template)
$message->setTemplateVar('discount_code', 'SAVE20');
$message->setTemplateVar('expiry_date', '2026-03-31');

// Attachments
$message->addAttachment(Attachment::fromFile('/path/to/invoice.pdf'));
$message->addAttachment(Attachment::fromString($csvContent, 'report.csv'));

$result = $api->getMessagesManager()->sendMail($message, $mailingListId, $campaignId);

echo 'Email ID: ' . $result->getEmailId();
echo 'Sending ID: ' . $result->getSendingId();
echo 'Status: ' . $result->getStatus()->value;
```

### Webhook Processing

The library can parse incoming webhook payloads from Mailkit for subscribe/unsubscribe events:

```php
// In your webhook endpoint handler
$payload = file_get_contents('php://input');

// Process a subscribe webhook
$subscribe = $api->getWebHooksManager()->processSubscribe($payload);
if ($subscribe !== null) {
    echo 'Subscribed: ' . $subscribe->getUser()->getEmail();
    echo 'IP: ' . $subscribe->getIp();
    echo 'Date: ' . $subscribe->getDate()->format('Y-m-d H:i:s');
    echo 'Channel: ' . $subscribe->getChannel();
}

// Process an unsubscribe webhook
$unsubscribe = $api->getWebHooksManager()->processUnsubscribe($payload);
if ($unsubscribe !== null) {
    echo 'Unsubscribed: ' . $unsubscribe->getUser()->getEmail();
    echo 'Method: ' . $unsubscribe->getMethod()->value;
}
```

Data Objects & Enums
--------------------

The library uses native PHP 8.1 backed enums:

| Enum | Type | Values |
|------|------|--------|
| `Gender` | `string` | `MALE` (`M`), `FEMALE` (`F`) |
| `UserStatus` | `string` | `ENABLED`, `DISABLED`, `UNKNOWN`, `TEMPORARY`, `PERMANENT`, `UNSUBSCRIBE` |
| `InsertStatus` | `int` | `UPDATE` (0), `INSERT` (1), `INSERT_UNSUBSCRIBE` (2), `UPDATE_UNSUBSCRIBE` (3), `FAULT` (4) |
| `SendMailResultStatus` | `int` | `UPDATE` (0), `INSERT` (1), `INSERT_UNSUBSCRIBE` (2), `UPDATE_UNSUBSCRIBE` (3), `FAULT` (4), `UPDATE_NOT_SENT` (6), `INSERT_NOT_SENT` (7) |
| `MailingListStatus` | `string` | `STATUS_ENABLED` (`enabled`), `STATUS_DISABLED` (`disabled`) |
| `UnsubscribeMethod` | `string` | `LINK_IN_MAIL`, `MANUAL`, `SPAM_REPORT`, `LIST_UNSUBSCRIBE_MAIL`, `API_UNSUBSCRIBE`, `LIST_UNSUBSCRIBE_ONECLICK`, `TIMEOUT` |

Usage:
```php
use Igloonet\MailkitApi\DataObjects\Enums\UserStatus;

$status = UserStatus::from('enabled');    // Create from value
$status = UserStatus::tryFrom('unknown'); // Returns null on invalid value
echo $status->value;                      // Get the scalar value
```

Error Handling
--------------

All API errors throw specific exception classes under the `Igloonet\MailkitApi\Exceptions` namespace:

- **User operations**: `UserCreationException`, `UserEditException`, `UserUnsubscribtionException`, `UserRevalidationException`, `UserStatusReceiveException`
- **Mailing lists**: `MailingListsLoadException`, `MailingListDeletionException`, `MailingListNotFoundException`
- **Messages**: `MessageSendException` (with subtypes for missing/invalid mailing list, campaign, sender, etc.)
- **RPC layer**: `BaseRpcException`, `BaseRpcResponseException`

All exceptions implement `MailkitApiException` and provide access to the underlying RPC response via
`getRpcResponse()` where applicable.

```php
use Igloonet\MailkitApi\Exceptions\User\UserCreationBadEmailSyntaxException;
use Igloonet\MailkitApi\Exceptions\User\UserCreationException;

try {
    $api->getUsersManager()->addUser($user, $mailingListId, doubleOptIn: true);
} catch (UserCreationBadEmailSyntaxException $e) {
    echo 'Invalid email: ' . $e->getMessage();
} catch (UserCreationException $e) {
    echo 'User creation failed: ' . $e->getRpcResponse()->getError();
}
```

Architecture
------------

```
src/
├── MailkitApi.php                 # Main entry point
├── DataObjects/                   # Data models
│   ├── User.php                   # Subscriber with 25 custom fields
│   ├── MailingList.php            # Mailing list
│   ├── Message.php                # Email message with attachments
│   ├── Attachment.php             # File or string attachment
│   ├── SubscribeWebHook.php       # Subscribe webhook payload
│   ├── UnsubscribeWebHook.php     # Unsubscribe webhook payload
│   └── Enums/                     # Native PHP 8.1 backed enums
├── Managers/                      # API endpoint managers
│   ├── UsersManager.php           # User CRUD, subscribe/unsubscribe
│   ├── MailingListsManager.php    # Mailing list CRUD
│   ├── MessagesManager.php        # Campaign mail sending
│   └── WebHooksManager.php        # Webhook payload processing
├── Results/
│   └── SendMailResult.php         # Sendmail response (emailId, sendingId, status)
├── Exceptions/                    # Exception hierarchy
└── RPC/                           # Transport layer
    ├── Client.php                 # Dispatches to XML/JSON adapter
    └── Adapters/
        ├── XmlAdapter.php         # XML-RPC via ext-xmlrpc
        └── JsonAdapter.php        # JSON via Nette\Utils\Json
```

Development
-----------

```sh
git clone git@github.com:Spoje-NET/mailkit-api.git
cd mailkit-api
composer install
```

### Available Make Targets

```sh
make tests                    # Run test suite (Nette Tester)
make static-code-analysis     # Run PHPStan
make cs                       # Fix coding standards (PHP CS Fixer)
make vendor                   # Install Composer dependencies
```

### Testing

Tests use [Nette Tester](https://tester.nette.org/) with `.phpt` test files located in
`tests/IgloonetTests/MailkitApi/`. Mock adapters simulate API responses using fixture
files from `tests/IgloonetTests/MailkitApi/mock-files/api-data/`.

```sh
make tests
# or directly:
vendor/bin/tester tests
```

### Static Analysis

```sh
make static-code-analysis
```

### Coding Standards

The project uses [PHP CS Fixer](https://cs.symfony.com/) with the
[ergebnis/php-cs-fixer-config](https://github.com/ergebnis/php-cs-fixer-config) Php81 ruleset.

```sh
make cs
```

API Documentation
-----------------

See the [Mailkit API documentation](https://www.mailkit.com/resources/api/api-introduction) for the
full list of available endpoints and their parameters.

This library wraps the following Mailkit API methods:

- `mailkit.mailinglist.list` — List all mailing lists
- `mailkit.mailinglist.create` — Create a mailing list
- `mailkit.mailinglist.delete` — Delete/flush a mailing list
- `mailkit.mailinglist.adduser` — Add a subscriber
- `mailkit.mailinglist.edituser` — Edit a subscriber
- `mailkit.email.getstatus` — Get subscriber status
- `mailkit.email.unsubscribe` — Unsubscribe a user
- `mailkit.email.revalidate` — Revalidate an unsubscribed user
- `mailkit.email.delete` — Delete a user
- `mailkit.sendmail` — Send a campaign email

Authors
-------

- [Vítězslav Dvořák](mailto:vitezslav.dvorak@spojenet.cz) — SpojeNet IT s.r.o.
- [Miroslav Čížek](mailto:cizek@iglonet.cz) — igloonet
- [Roman Braciník](mailto:fejjo@igloonet.cz) — igloonet

License
-------

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

