<?php

App::uses('AbstractTransport', 'Network/Email');

class QueueTransport extends AbstractTransport
{

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
        $filename = tempnam($this->_config['queueFolder'], time() . '.QUEUE.');
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
     * @return mixed oldest file in queue or false if there is no file or there was an error
     */
    public function getNext()
    {

        if (!is_dir($this->_config['queueFolder']))
            return false;
        $dh = opendir($this->_config['queueFolder']);
        while ($next = readdir($dh))
        {
            $full_path = $this->_config['queueFolder'] . DS . $next;
            if (is_file($full_path))
            {
                closedir($dh);
                return $full_path;
            }
        }
        closedir($dh);

        return false;
    }

}

class QueueFileException extends CakeException
{

    protected $_messageTemplate = 'File %s could not be locked for queueing.';

}