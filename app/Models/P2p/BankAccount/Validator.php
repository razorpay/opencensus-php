<?php

namespace Rzp\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected $createRules = [
        Entity::ID                       => 'required|string|regex:^ba_(.*){14}$',
        Entity::ENTITY                   => 'required|string',
        Entity::IFSC                     => 'required|string|regex:^[\w]{11}$',
        Entity::BANK_NAME                => 'required|string|regex:^(.*)$',
        Entity::BENEFICIARY_NAME         => 'required|string|regex:^(.*)$',
        Entity::MASKED_ACCOUNT_NUMBER    => 'required|string|regex:^(.*)$',
        Entity::CREDS                    => 'required|string',
        Entity::CL_REGISTRATION_FORMAT   => 'required|string',
        Entity::REFRESHED_AT             => 'required|string',
        Entity::CREATED_AT               => 'required|string',
    ];

    protected $fetchBanksRules = [
    ];

    protected $retrieveRules = [
    ];

    protected $fetchAllRules = [
    ];

    protected $fetchRules = [
    ];

    protected $initiateSetUpiPinRules = [
    ];

    protected $setUpiPinRules = [
    ];

    protected $initiateFetchBalanceRules = [
    ];

    protected $fetchBalanceRules = [
    ];
}
