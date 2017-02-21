<?php

namespace RZP\Models\BankTransferAttempt;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'bank_transfer_attempt';

    protected $appFetchParamRules = [
        Entity::ENTITY_TYPE     => 'sometimes|string|in:settlement',
        Entity::ENTITY_ID       => 'sometimes|string|size:14',
        Entity::STATUS          => 'sometimes|string|size:1',
        Entity::UTR             => 'sometimes|alpha_num',
        Entity::REMARKS         => 'sometimes|string',
        Entity::DATETIME        => 'sometimes|string',
        Entity::CMS_REF_NO      => 'sometimes|string',
    ];
}