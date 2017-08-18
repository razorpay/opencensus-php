<?php

namespace App\Gateway\Blade;

use RZP\Models\Card\Network;

class ECI
{
    /**
     * ECI
     *
     * Electronic Commerce Indicator (ECI) is a value that is returned from the Directory Server (Visa, MasterCard, and JCB)
     * to indicate the authentication results of your customer's credit card payment on 3D Secure.
     *
     * VISA
     * Value    Definition
     * 05       Both cardholder and card issuing bank are 3D enabled. 3D card authentication is successful
     * 06       Either cardholder or card issuing bank is not 3D enrolled. 3D card authentication is unsuccessful, in sample situations as:
     *             1. 3D cardholder not enrolled
     *             2. Card issuing bank is not 3D Secure ready
     * 07       Authentication is unsuccessful or not attempted. The credit card is either a non-3D card or card issuing bank does not handle it as a 3D transaction
     *
     * MasterCard
     * Value    Definition
     * 00       Authentication is unsuccessful or not attempted. The credit card is either a non-3D card or card issuing bank does not handle it as a 3D transaction
     * 01       Either cardholder or card issuing bank is not 3D enrolled. 3D card authentication is unsuccessful, in sample situations as:
     *             1. 3D Cardholder not enrolled
     *             2. Card issuing bank is not 3D Secure ready
     * 02       Both cardholder and card issuing bank are 3D enabled. 3D card authentication is successful
     *
     * JCB
     * Value    Definition
     * 05       Both cardholder and card issuing bank are 3D enabled. 3D card authentication is successful
     * 06       Either cardholder or card issuing bank is not 3D enrolled. 3D card authentication is unsuccessful, in sample situations as:
     *             1. 3D Cardholder not enrolled
     *             2. Card issuing bank is not 3D Secure ready
     * 07       Authentication is unsuccessful or not attempted. The credit card is either a non-3D card or card issuing bank does not handle it as a 3D transaction
    **/

    protected static $eci = [
        Network::VISA => [
            AuthenticateStatus::Y => '05',
            AuthenticateStatus::U => '06',
            AuthenticateStatus::N => '06',
            AuthenticateStatus::F => '06',
        ],

        Network::MC => [
            AuthenticateStatus::Y => '02',
            AuthenticateStatus::U => '01',
            AuthenticateStatus::N => '01',
            AuthenticateStatus::F => '00',
        ],

        Network::JCB => [
            AuthenticateStatus::Y => '05',
            AuthenticateStatus::U => '06',
            AuthenticateStatus::N => '06',
            AuthenticateStatus::F => '07',
        ],
    ];

    public static function getValue($status, $network)
    {
        return self::$eci[$network][$status];
    }
}
