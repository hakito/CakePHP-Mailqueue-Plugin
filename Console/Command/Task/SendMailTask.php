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
        while ($queueFile = $queueMailer->transportClass()->getNext())
        {
            $delFile = false;
            $fh = fopen($queueFile, 'r');
            if (flock($fh, LOCK_EX))
            {
                $serialized = fread($fh, filesize($queueFile));
                $cakeMail = unserialize($serialized);
                if ($realMailer->transportClass()->send($cakeMail))
                {
                    $delFile = true;
                }
                flock($fh, LOCK_UN);
            }
            fclose($fh);
            if ($delFile)
                unlink($queueFile);
        }
    }

}

class MailqueueArgumentException extends Exception
{

}