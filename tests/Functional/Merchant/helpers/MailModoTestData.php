<?php

use RZP\Error\ErrorCode;

return [

    'testRegister' => [
        'request'  => [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'test5@c.com',
                'password'              => 'hello1233',
                'password_confirmation' => 'hello1233',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'test5@c.com',
            ]
        ]
    ],
];
