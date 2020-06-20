<?php

namespace MailQueue\Mailer;

use \Cake\Mailer\Email;

class PersistableMail extends Email
{
    public function __construct(Email $email = null)
    {
        if ($email === null)
            return;
        $this->createFromArray($email->jsonSerialize());
        $this->_message = $email->message();
        $this->_htmlMessage = $email->message(Email::MESSAGE_HTML);
        $this->_textMessage = $email->message(Email::MESSAGE_TEXT);
    }

    public function jsonSerialize()
    {
        $array = parent::jsonSerialize();

        $properties = ['_message', '_htmlMessage', '_textMessage'];
        foreach($properties as $property)
            $array[$property] = $this->{$property};
        return $array;
    }

    public function setTransportInstance($transportInsance)
    {
        $this->_transport = $transportInsance;
    }
}