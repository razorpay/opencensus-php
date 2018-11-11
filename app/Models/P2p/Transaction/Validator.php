<?php

namespace Rzp\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected $createRules = [
        Entity::ID                   => 'required|string|regex:^ctxn_[\w]{14}$',
        Entity::ENTITY               => 'required|string',
        Entity::TXN_ID               => 'required|string|regex:^(.*)$',
        Entity::STATUS               => 'required|string|regex:^(.*)$',
        Entity::AMOUNT               => 'required|string',
        Entity::DESCRIPTION          => 'required|string|regex:^(.*)$',
        Entity::TYPE                 => 'required|string|regex:^(.*)$',
        Entity::CURRENCY             => 'required|string|regex:^(.*)$',
        Entity::ERROR_DESCRIPTION    => 'required|string',
        Entity::ERROR_CODE           => 'required|string',
        Entity::TRANSACTION_TYPE     => 'required|string|regex:^(.*)$',
        Entity::RRN                  => 'required|string|regex:^(.*)$',
        Entity::CREATED_AT           => 'required|string',
        Entity::COMPLETED_AT         => 'required|string',
        Entity::EXPIRE_AT            => 'required|string',
    ];

    protected $initiatePayRules = [
    ];

    protected $initiateCollectRules = [
    ];

    protected $fetchAllRules = [
    ];

    protected $fetchRules = [
    ];

    protected $initiateAuthorizeRules = [
    ];

    protected $authorizeRules = [
    ];

    protected $rejectRules = [
    ];
}
