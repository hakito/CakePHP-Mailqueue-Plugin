<?php

namespace MailQueue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Mailer\Mailer;
use MailQueue\Mailer\Transport\QueueTransport;

class SendMailCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        try
        {
            if (sizeof($args->getArguments()) < 2)
            {
                throw new \InvalidArgumentException('Required arguments "queue-profile", "real-send-profile"');
            }

            $queueMailer = new Mailer($args->getArgumentAt(0));
            $realMailer = new Mailer($args->getArgumentAt(1));

            /** @var QueueTransport */
            $transport = $queueMailer->getTransport();
            $transport->flush($realMailer->getTransport());
        }
        catch (\InvalidArgumentException $e)
        {
            $io->error($e->getMessage());
            $this->abort(1);
        }
    }

}