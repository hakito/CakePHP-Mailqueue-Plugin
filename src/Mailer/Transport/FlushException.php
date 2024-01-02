<?php

namespace MailQueue\Mailer\Transport;

class FlushException extends \Cake\Core\Exception\CakeException
{

    protected string $_messageTemplate = 'Could not get a lock for %s';

}