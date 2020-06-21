<?php

namespace MailQueue\Mailer;

use Cake\Mailer\Message;

class PersistableMessage extends Message
{
    public function __construct(Message $email = null)
    {
        if ($email === null)
            return;
        $this->createFromArray($email->jsonSerialize());
        $this->_message = $email->message;
        $this->_htmlMessage = $email->htmlMessage;
        $this->_textMessage = $email->textMessage;
    }

    public function jsonSerialize(): array
    {
        $array = parent::jsonSerialize();

        $properties = ['_message', '_htmlMessage', '_textMessage'];
        foreach($properties as $property)
            $array[$property] = $this->{$property};
        return $array;
    }
}