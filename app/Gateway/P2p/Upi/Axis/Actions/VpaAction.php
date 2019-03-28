<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Gateway\P2p\Upi\Axis\Fields;

class VpaAction extends Action
{
    const VPA_AVAILABILITY                  = 'VPA_AVAILABILITY';

    const LINK_ACCOUNT                      = 'LINK_ACCOUNT';

    const MAP = [
        self::VPA_AVAILABILITY => [
            self::VALIDATOR => [
                Fields::CUSTOMER_VPA   => 'required',
            ]
        ],
        self::LINK_ACCOUNT => [
            self::VALIDATOR => [
                Fields::CUSTOMER_VPA            => 'required',
                Fields::ACCOUNT_REFERENCE_ID    => 'required',
            ]
        ]
    ];
}
