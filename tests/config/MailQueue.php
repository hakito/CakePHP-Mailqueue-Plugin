<?php
return [
    'EmailTransport' => [
        'MailQueue' => [
            // required
            'className' => \MailQueue\Mailer\Transport\QueueTransport::class,
            'queueFolder' => TMP . 'mailqueue', // storage location for mailqueue

            // optional:
            'requeue' => [300, 500, 1000] // requeue after x seconds in case of an error
        ]
    ],

    'Email' => [
        'default' => [
            'transport' => 'MailQueue',
                'from' => 'your.name@example.com',
        ]
    ]
];