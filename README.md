[![Latest Stable Version](https://poser.pugx.org/hakito/cakephp-mailqueue-plugin/v/stable.svg)](https://packagist.org/packages/hakito/cakephp-mailqueue-plugin) [![Total Downloads](https://poser.pugx.org/hakito/cakephp-mailqueue-plugin/downloads.svg)](https://packagist.org/packages/hakito/cakephp-mailqueue-plugin) [![Latest Unstable Version](https://poser.pugx.org/hakito/cakephp-mailqueue-plugin/v/unstable.svg)](https://packagist.org/packages/hakito/cakephp-mailqueue-plugin) [![License](https://poser.pugx.org/hakito/cakephp-mailqueue-plugin/license.svg)](https://packagist.org/packages/hakito/cakephp-mailqueue-plugin)

CakePHP-Mailqueue-Plugin
========================

Plugin to store mail in a queue for later sendout.

When working with emails on a webservice sending email blocks the http request until the email is actually sent out. This can be frustrating for a user especially if the smtp server does not respond promptly.

With this plugin you can save the mail to a local queue file and invoke the actual transport for example with a cron fired cake shell command.

Installation
-------------

Download the plugin to app/Plugin/Mailqueue.

Configuration
-------------

Add a new entry to the app/Config/email.php or replace it with an existing one.

```php
class EmailConfig
{
    public $queue = array(
        // required:
        'transport' => 'Mailqueue.Queue',
        'from' => 'your.name@example.com',

        // optional:
        'queueFolder' => '/tmp/mailqueue', // storage location for mailqueue
        'requeue' => array(300, 500, 1000) // requeue after x seconds in case of an error
    );

    // Your existing config
    public $smtp = array(...)
}
```

Queue a mail
------------

Send mail to the queue as usually in cake

```php
App::uses('CakeEmail', 'Network/Email');
$email = new CakeEmail('queue');
// place your content in $email
$email->send();
```

Do the real sendout
-------------------

Use cake shell to do the real sendout. The shell script requires 2 arguments. The first is the name of your queue configuration and the second the name of the config to use for the real sendout.

In your app directory execute:

```sh
Console/cake Mailqueue.SendMail queue smtp
```

Donate
------
Every donation is welcome

* PayPal: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7L95X7SB9B42N
* Bitcoin: 1NUrWv9zkYaKjLf8zGwk1R6WtgxFPnEBcP
