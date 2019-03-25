<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Gateway\P2p\Upi\Axis\Fields;

class BankAccountAction extends Action
{
    const GET_ACCOUNTS                  = 'GET_ACCOUNTS';

    const SET_MPIN                      = 'SET_MPIN';

    const CHANGE_MPIN                   = 'CHANGE_MPIN';

    const CHECK_BALANCE                 = 'CHECK_BALANCE';

    const MAP = [
        self::GET_ACCOUNTS => [
            self::VALIDATOR => [
                Fields::BANK_CODE   => 'required',
            ]
        ],
        self::SET_MPIN => [
            self::VALIDATOR => [

            ]
        ],
        self::CHANGE_MPIN => [
            self::VALIDATOR => [

            ]
        ],
        self::CHECK_BALANCE => [
            self::VALIDATOR => [

            ]
        ]
    ];
}
