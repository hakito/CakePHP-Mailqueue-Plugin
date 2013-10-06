<?php

class SendMailShell extends AppShell
{

    public $tasks = array('Mailqueue.SendMail');

    public function main()
    {
        $this->SendMail->args = $this->args;
        try
        {
            $this->SendMail->execute();
        }
        catch (MailqueueArgumentException $e)
        {
            $this->out($e->getMessage());
            exit(1);
        }
    }

}