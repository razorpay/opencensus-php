<?php

namespace RZP\Models\Merchant;

use RZP\Models\Payment\Gateway;

class Preferences
{
    /**
     * This needs to go in DB, for hotfix we are keeping it here
     * Maintains lits of gateways excluded for a merchant
     */
    const MERCHANT_TERMINAL_EXCLUDE_LIST = [
        // 1MG
        '6e9vU1F6c16Wgy' => [
            Gateway::HDFC,
            Gateway::CYBERSOURCE,
        ],

        // NETMEDS
        '4eG3tTq19vAYxo' => [
            Gateway::HDFC,
            Gateway::CYBERSOURCE,
        ],

        // Soch
        '6QGdVzDAIpBniU' => [
            Gateway::HDFC,
        ]
    ];
}