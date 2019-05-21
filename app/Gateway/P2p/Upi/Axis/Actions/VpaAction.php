<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\S2sDirect;

class VpaAction extends Action
{
    const VPA_AVAILABILITY                  = 'VPA_AVAILABILITY';

    const LINK_ACCOUNT                      = 'LINK_ACCOUNT';

    const VALIDATE_VPA                      = 'VALIDATE_VPA';

    const BLOCK_VPA                         = 'BLOCK_VPA';

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
        ],
        self::VALIDATE_VPA  => [
            self::SOURCE    => self::DIRECT,
            self::DIRECT    => [
                S2sDirect::METHOD => 'post'
            ],
        ],
        self::BLOCK_VPA  => [
            self::SOURCE  => self::DIRECT,
            self::DIRECT  => [
                S2sDirect::METHOD => 'post'
            ],
        ]
    ];
}
