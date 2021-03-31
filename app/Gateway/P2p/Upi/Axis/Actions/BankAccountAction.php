<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\S2sDirect;

class BankAccountAction extends Action
{
    const GET_ACCOUNTS                  = 'GET_ACCOUNTS';

    const SET_MPIN                      = 'SET_MPIN';

    const CHANGE_MPIN                   = 'CHANGE_MPIN';

    const CHECK_BALANCE                 = 'CHECK_BALANCE';

    const RETRIEVE_BANKS                = 'RETRIEVE_BANKS';

    const MAP = [
        self::GET_ACCOUNTS => [
            self::VALIDATOR => [
                Fields::BANK_CODE   => 'required',
            ]
        ],
        self::SET_MPIN => [
            self::VALIDATOR => [
                Fields::CUSTOMER_VPA          => 'required',
                Fields::CARD                  => 'required',
                Fields::EXPIRY                => 'required',
                Fields::ACCOUNT_REFERENCE_ID  => 'required',
                Fields::UPI_REQUEST_ID        => 'required'
            ]
        ],
        self::CHANGE_MPIN => [
            self::VALIDATOR => [
                    Fields::ACCOUNT_REFERENCE_ID  => 'required',
                    Fields::UPI_REQUEST_ID        => 'required'
            ]
        ],
        self::CHECK_BALANCE => [
            self::VALIDATOR => [
                Fields::ACCOUNT_REFERENCE_ID => 'required',
                Fields::UPI_REQUEST_ID       => 'required'
            ]
        ],
        self::RETRIEVE_BANKS     => [
            self::SOURCE  => self::DIRECT,
            self::DIRECT  => [
                S2sDirect::METHOD               => 'get',
                S2sDirect::SKIP_STATUS_CHECK    => true,
                S2sDirect::SKIP_AUTH_HEADERS    => true,
            ],
        ],
    ];
}
