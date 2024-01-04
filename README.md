
CakePHP-Mailqueue-Plugin
========================

[![Build Status](https://app.travis-ci.com/hakito/CakePHP-Mailqueue-Plugin.svg?branch=master)](https://app.travis-ci.com/hakito/CakePHP-Mailqueue-Plugin)
[![Latest Stable Version](https://poser.pugx.org/hakito/cakephp-mailqueue-plugin/v/stable.svg)](https://packagist.org/packages/hakito/cakephp-mailqueue-plugin) [![Total Downloads](https://poser.pugx.org/hakito/cakephp-mailqueue-plugin/downloads.svg)](https://packagist.org/packages/hakito/cakephp-mailqueue-plugin) [![Latest Unstable Version](https://poser.pugx.org/hakito/cakephp-mailqueue-plugin/v/unstable.svg)](https://packagist.org/packages/hakito/cakephp-mailqueue-plugin) [![License](https://poser.pugx.org/hakito/cakephp-mailqueue-plugin/license.svg)](https://packagist.org/packages/hakito/cakephp-mailqueue-plugin)

CakePHP 5.x plugin to store mail in a queue for later sendout.

When working with emails on a webservice sending email blocks the http request until the email is actually sent out. This can be frustrating for a user especially if the smtp server does not respond promptly.

With this plugin you can save the mail to a local queue file and invoke the actual transport for example with a cron fired cake shell command.

Installation
-------------

If you are using composer simply add it with:

```bash
composer require hakito/cakephp-mailqueue-plugin
```

Otherwise download the plugin to app/Plugin/Mailqueue.

Load the plugin in your bootstrap method

```php
$this->addPlugin('MailQueue');
```

Configuration
-------------

Add a transport entry to your app_local.php

```php
'EmailTransport' => [
    'MailQueue' => [
        // required
        'className' => \MailQueue\Mailer\Transport\QueueTransport::class,
        'queueFolder' => TMP . 'mailqueue', // storage location for mailqueue

        // optional:
        'requeue' => [300, 500, 1000] // requeue after x seconds in case of an error
    ]
],

'Email' => [
    'default' => [
        'transport' => 'MailQueue',
    ],
    'smtp' => [
        // configure your real mailer here
    ]
]
```

Queue a mail
------------

Send mail to the queue as usually in cake

```php
$mailer = new Mailer('default');
// place your content in $email
$mailer->deliver();
```

Do the real sendout
-------------------

Use cake shell to do the real sendout. The shell script requires 2 arguments. The first is the name of your queue configuration and the second the name of the config to use for the real sendout.

In your app directory execute:

```sh
bin/cake MailQueue SendMail smtp
```
