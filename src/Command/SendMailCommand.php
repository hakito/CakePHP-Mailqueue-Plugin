<?php

namespace MailQueue\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;

class SendMailCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        try
        {
            if (sizeof($args->args) < 2)
            {
                throw new \InvalidArgumentException('Required arguments "queue-name", "transport configuration name"');
            }

            $queueMailer = new Email($args->getArgumentAt(0));
            $realMailer = new Email($args->getArgumentAt(1));
            $queueMailer->transportClass()->flush($realMailer);
        }
        catch (\InvalidArgumentException $e)
        {
            $io->error($e->getMessage());
            $this->abort(1);
        }
    }

}