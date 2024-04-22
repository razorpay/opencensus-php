<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive\Actions;


use RZP\Gateway\P2p\Upi\AxisOlive\S2sMozart;

/**
 * Client action class to hold information regarding method , resource
 * Class ClientAction
 *
 * @package RZP\Gateway\P2p\Upi\AxisOlive\Actions
 */

class ClientAction extends Action
{
    const GET_GATEWAY_CONFIG   = 'GET_GATEWAY_CONFIG';

    const GATEWAY_CONFIG       = 'GATEWAY_CONFIG';

    const SERVER_TOKEN         = 'server_token';

    const REWARD_ELIGIBILITY   = 'reward_eligibility';

    const GET_CUSTOMER_REWARD_ELIGIBILITY = 'GET_CUSTOMER_REWARD_ELIGIBILITY';
    const ALLOT_CUSTOMER_REWARD           = 'ALLOT_CUSTOMER_REWARD';

    const REWARD_ALLOT                    = 'reward_allot';

    const MAP = [
        self::GET_GATEWAY_CONFIG => [
            self::SOURCE    => self::MOZART,
            self::MOZART    => [
                S2sMozart::METHOD       => 'post',
                S2sMozart::RESOURCE     => self::SERVER_TOKEN,
                S2sMozart::ROOT_RESOURCE => 'upiPayments',
            ],
        ],
        self::GET_CUSTOMER_REWARD_ELIGIBILITY => [
            self::SOURCE   => self::MOZART,
            self::MOZART   => [
                S2sMozart::METHOD => 'post',
                S2sMozart::RESOURCE => self::REWARD_ELIGIBILITY,
                S2sMozart::ROOT_RESOURCE => 'upiPayments',
            ]
        ],
        self::ALLOT_CUSTOMER_REWARD => [
            self::SOURCE   => self::MOZART,
            self::MOZART   => [
                S2sMozart::METHOD => 'post',
                S2sMozart::RESOURCE => self::REWARD_ALLOT,
                S2sMozart::ROOT_RESOURCE => 'upiPayments',
            ],
        ],
    ];
}
