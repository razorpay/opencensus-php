<?php

namespace RZP\Gateway\Worldline\Mock;

use RZP\Base;
use RZP\Gateway\Worldline\Fields;

class Validator extends Base\Validator
{
    protected static $verifyRules = [
        Fields::FROM_ENTITY      => 'required|alpha_num|max:255',
        Fields::VERIFY_BANK_CODE => 'required|alpha_num|max:255',
        Fields::DATA             => ['required', [
                Fields::TID     => 'required|alpha_num|max:255',
                Fields::AMOUNT  => ['required', 'regex:/^\d*(\.\d{2})$/'],
                Fields::TXN_ID  => 'required|alpha_num|max:255',
                Fields::TR_ID   => 'required|alpha_num|max:255',
            ]
        ]
    ];

    protected static $refundRules = [
        Fields::FROM_ENTITY      => 'required|alpha_num|max:255',
        Fields::VERIFY_BANK_CODE => 'required|alpha_num|max:255',
        Fields::DATA             => ['required', [
                Fields::TXN_TYPE         => 'required|numeric',
                Fields::TID              => 'required|alpha_num|max:255',
                Fields::RRN              => 'required|numeric',
                Fields::REFUND_AUTH_CODE => 'required|alpha_num|max:255',
                Fields::REFUND_AMOUNT    => ['required', 'regex:/^\d*(\.\d{2})$/'],
                Fields::REFUND_REASON    => 'required|alpha_num|max:255',
                Fields::MOBILE_NUMBER    => 'required|numeric',
                Fields::REFUND_ID        => 'required|alpha_num|max:255',
            ]
        ]
    ];
}
