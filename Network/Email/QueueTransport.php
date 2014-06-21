<?php

App::uses('AbstractTransport', 'Network/Email');

class QueueTransport extends AbstractTransport
{

    private static $fileSuffix = '.QUEUE.';

    public function config($config = null)
    {
        $default = array(
            'queueFolder' => TMP . 'mailqueue',
        );
        $this->_config = $config + $default;
    }

    public function queueFolder($queueFolder)
    {
        $this->_config['queueFolder'] = $queueFolder;
    }

    public function send(CakeEmail $email)
    {
        if (!is_dir($this->_config['queueFolder']))
            mkdir($this->_config['queueFolder']);

        // create unique name
        $filename = tempnam($this->_config['queueFolder'], time() . self::$fileSuffix);
        $fh = fopen($filename, 'w');
        if (flock($fh, LOCK_EX))
        {
            fwrite($fh, serialize($email));
            chmod($filename, 0666);
            flock($fh, LOCK_UN);
        }
        else
        {
            throw new QueueFileException(array('filename' => $filename));
        }

        fclose($fh);
        return array('filename' => $filename);
    }

    /**
     * Sendout mail queue using the given real mailer
     * @param CakeEmail $realMailer
     */
    public function flush($realMailer)
    {
        $lockfile = $this->_config['queueFolder'] . DS . 'flush.lock';
        touch($lockfile);
        $lock = fopen($lockfile, 'r');
        if (!flock($lock, LOCK_EX))
        {            
            fclose($lock);
            throw new Exception('Could not get a lock for ' . $lockfile);
        }

        $processed = array();
        while ($queueFile = $this->getNext())
        {
            if (array_key_exists($queueFile, $processed))
                throw new LogicException(sprintf('Trying to process queue file "%s" more than once.', $queueFile));

            $processed[$queueFile] = true;
            
            $delFile = false;
            $fh = fopen($queueFile, 'r');
            if (flock($fh, LOCK_EX))
            {
                $serialized = fread($fh, filesize($queueFile));
                $cakeMail = unserialize($serialized);
                
                $delFile = $this->tryRealSend($realMailer, $cakeMail);
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
     * @param CakeMail $realMailer
     * @param CakeMail $cakeMail queued mail
     * @return boolean true on success
     * @throws SocketException
     */
    private function tryRealSend($realMailer, $cakeMail)
    {
        try
        {
            if ($realMailer->transportClass()->send($cakeMail))
                return true;
        }
        catch (SocketException $e)
        {
            CakeLog::error("Mailqueue real send: " . $e->getMessage(), 'Mailqueue');
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

class QueueFileException extends CakeException
{

    protected $_messageTemplate = 'File %s could not be locked for queueing.';

}
