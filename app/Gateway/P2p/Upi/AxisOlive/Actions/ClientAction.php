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

    const MAP = [
        self::GET_GATEWAY_CONFIG => [
            self::SOURCE    => self::MOZART,
            self::MOZART    => [
                S2sMozart::METHOD       => 'post',
                S2sMozart::RESOURCE     => self::SERVER_TOKEN,
            ],
        ]
    ];
}
