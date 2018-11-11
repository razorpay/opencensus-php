<?php

namespace Rzp\Models\P2p\BankAccount\Bank;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected $createRules = [
        Entity::IFSC             => 'required|string|regex:^[\w]{13}$',
        Entity::NAME             => 'required|string',
        Entity::UPI_IIN          => 'required|string|regex:^[\d]{6}$',
        Entity::UPI_FORMAT       => 'required|string',
        Entity::REFRESHED_AT     => 'required|string',
        Entity::SPOC             => 'required|string',
    ];

    protected $fetchAllRules = [
    ];
}
