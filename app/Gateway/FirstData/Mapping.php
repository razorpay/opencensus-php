<?php

namespace RZP\Gateway\FirstData;

use RZP\Gateway\Base;
use RZP\Models\Card;

class Mapping extends Base\Mapping
{
    const PAYMENT_METHOD_CODES = array(
        // MasterCard
        Card\Network::MC    => 'M',
        // Visa
        Card\Network::VISA  => 'V',
        // American Express
        Card\Network::AMEX  => 'A',
        // Diners
        Card\Network::DICL  => 'C',
        // JCB
        Card\Network::JCB   => 'J',
        // Maestro
        Card\Network::MAES  => 'MA',
        //RuPay
        Card\Network::RUPAY => 'RU',
    );
}
