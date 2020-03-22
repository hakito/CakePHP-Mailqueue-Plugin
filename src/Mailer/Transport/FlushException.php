<?php

namespace MailQueue\Mailer\Transport;

class FlushException extends \Cake\Core\Exception\Exception
{

    protected $_messageTemplate = 'Could not get a lock for %s';

}