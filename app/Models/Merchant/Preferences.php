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
        // Soch
        '6QGdVzDAIpBniU' => [
            Gateway::HDFC,
        ],

        // Zomato
        '6H7N6hlcv29OMG' => [
            Gateway::AXIS_MIGS,
            Gateway::FIRST_DATA,
        ],
    ];
}
