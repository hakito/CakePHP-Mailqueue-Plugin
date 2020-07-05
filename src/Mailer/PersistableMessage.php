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
        $this->message = $email->message;
        $this->htmlMessage = $email->htmlMessage;
        $this->textMessage = $email->textMessage;
    }

    public function jsonSerialize(): array
    {
        $array = parent::jsonSerialize();

        $properties = ['message', 'htmlMessage', 'textMessage'];
        foreach($properties as $property)
            $array[$property] = $this->{$property};
        return $array;
    }
}