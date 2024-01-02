<?php

namespace MailQueue\Mailer\Transport;

class QueueFileException extends \Cake\Core\Exception\CakeException
{

    protected $_messageTemplate = 'File %s could not be locked for queueing.';

}