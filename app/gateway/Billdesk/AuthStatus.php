<?php

namespace Gateway\Billdesk;

class AuthStatus
{
    const SUCCESS = '0300';

    public static $codes = array(
        '0300',
        '0399',
        'NA',
        '0002',
        '0001',
    );

    public static $messages = array(
        '0300' => 'Success',
    );
}