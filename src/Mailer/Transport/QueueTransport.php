<?php

namespace MailQueue\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;
use Cake\Log\Log;

use MailQueue\Mailer\PersistableMessage;

class QueueTransport extends AbstractTransport
{

    private static $fileSuffix = '.QUEUE.';

    public function send(Message $message): array
    {
        if (!is_dir($this->_config['queueFolder']))
            mkdir($this->_config['queueFolder']);

        // create unique name
        $filename = tempnam($this->_config['queueFolder'], time() . self::$fileSuffix);
        $fh = fopen($filename, 'w');
        if (flock($fh, LOCK_EX))
        {
            $persistableMail = new PersistableMessage($message);
            $serialized = serialize($persistableMail->jsonSerialize());
            fwrite($fh, $serialized);
            chmod($filename, 0666);
            flock($fh, LOCK_UN);
        }
        else
        {
            throw new QueueFileException(['filename' => $filename]);
        }

        fclose($fh);
        return ['filename' => $filename];
    }

    /**
     * Sendout mail queue using the given real mailer
     * @param AbstractTransport $realTransport
     */
    public function flush(AbstractTransport $realTransport, bool $noBlock = false)
    {
        $lockfile = $this->_config['queueFolder'] . DS . 'flush.lock';
        touch($lockfile);
        $lock = fopen($lockfile, 'r');
        $noBlock = $noBlock ? LOCK_NB : 0;
        if (!flock($lock, LOCK_EX | $noBlock))
        {
            fclose($lock);
            throw new FlushException(['filename' => $lockfile]);
        }

        $processed = [];
        while ($queueFile = $this->getNext())
        {
            if (array_key_exists($queueFile, $processed))
                throw new \LogicException(sprintf('Trying to process queue file "%s" more than once.', $queueFile));

            $processed[$queueFile] = true;

            $delFile = false;
            $fh = fopen($queueFile, 'r');
            if (flock($fh, LOCK_EX))
            {
                $serialized = fread($fh, filesize($queueFile));
                $message = new PersistableMessage();
                $message->createFromArray(unserialize($serialized));

                $delFile = $this->tryRealSend($realTransport, $message);
                if ($delFile === false)
                {
                    $this->tryRequeue($queueFile);
                }
                flock($fh, LOCK_UN);
            }
            fclose($fh);
            if ($delFile === true)
                unlink($queueFile);
        }

        flock($lock, LOCK_UN);
        fclose($lock);
    }

    private function tryRequeue($queueFile)
    {
        $filename = basename($queueFile);
        $dirname = dirname($queueFile);
        $targetFilename = 'UNSENT.' . $filename;

        if (!empty($this->_config['requeue']))
        {
            $parts = explode(self::$fileSuffix, $queueFile);
            $rparts = explode('.R.', $parts[1], 2);
            $retry = 0;
            $tempnamSuffix = 0;

            if (sizeof($rparts) > 1 && is_numeric($rparts[0]))
            {
                $tempnamSuffix = 1;
                $retry = $rparts[0] + 1;
            }

            if (isset($this->_config['requeue'][$retry]))
            {
                $future = max(0, $this->_config['requeue'][$retry]);
                $targetFilename = (time() + $future) . self::$fileSuffix . $retry . '.R.' . $rparts[$tempnamSuffix];
            }
        }

        rename($queueFile,  $dirname . DS . $targetFilename);
    }

    /**
     * Trys to do the real sendout. In case of a SockeException the mail will
     * @param AbstractTransport $realTransport
     * @param PersistableMessage $message queued mail
     * @return boolean true on success
     */
    private function tryRealSend(AbstractTransport $realTransport, PersistableMessage $message)
    {
        try
        {
            $realTransport->send($message);
            return true;
        }
        catch (\Exception $e)
        {
            $logMessage = sprintf("(%s) %s @ %s:%s", $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
            Log::error("Mailqueue real send: " . $logMessage, 'MailQueue');
        }
        return false;
    }

    /**
     * @return mixed oldest file in queue or false if there is no file or there was an error
     */
    private function getNext()
    {

        if (!is_dir($this->_config['queueFolder']))
            return false;

        $now = time();
        $dh = opendir($this->_config['queueFolder']);
        while ($next = readdir($dh))
        {
            $full_path = $this->_config['queueFolder'] . DS . $next;

            if (!is_file($full_path))
                continue;

            $parts = explode(self::$fileSuffix, $next);
            if (sizeof($parts) != 2)
                continue;

            $timestamp = $parts[0];
            if (!is_numeric($timestamp))
                continue;

            if ($timestamp > $now)
                continue;

            closedir($dh);
            return $full_path;
        }
        closedir($dh);

        return false;
    }

}


