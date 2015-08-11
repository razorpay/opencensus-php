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
        '0300'  => 'Success',
        '0399'  => 'Invalid Authentication at Bank Cancel Transaction',
        'NA'    => 'Invalid Input in the Request Message Cancel Transaction',
        '0002'  => 'BillDesk is waiting for Response from Bank Pending Transaction',
        '0001'  => 'Error at BillDesk Cancel Transaction',
    );
}
