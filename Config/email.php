<?php

class EmailConfig
{

    public $queue = array(
        // required:
        'transport' => 'Mailqueue.Queue',
        'from' => 'your.name@example.com',

        // optional:
        'queueFolder' => '/tmp/mailqueue', // storage location for mailqueue
        'requeue' => array(300, 500, 1000) // requeue after x seconds in case of an error
    );

}