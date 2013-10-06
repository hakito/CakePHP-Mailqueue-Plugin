<?php

class EmailConfig
{

    public $queue = array(
        // required:
        'transport' => 'Mailqueue.Queue',
        'from' => 'your.name@example.com',
        // optional:
        'queueFolder' => '/tmp/mailqueue' // storage location for mailqueue
    );

}