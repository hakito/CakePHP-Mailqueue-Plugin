<?php

App::uses('CakeEmail', 'Network/Email');

class SendMailTask extends Shell
{

    public function execute()
    {
        if (empty($this->args[0]) || empty($this->args[1]))
        {
            throw new MailqueueArgumentException('Rquired arguments "queue-name", "transport configuration name"');
        }

        $queueMailer = new CakeEmail($this->args[0]);
        $realMailer = new CakeEmail(empty($this->args[1]));
        $queueMailer->transportClass()->flush($realMailer);
    }

}

class MailqueueArgumentException extends Exception
{

}