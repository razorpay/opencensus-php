<?php

namespace RZP\Gateway\Enach\Base;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'enach';

    protected $appFetchParamRules = [
        Entity::PAYMENT_ID          => 'sometimes|string|size:14',
        Entity::UMRN                => 'sometimes|string',
    ];

    public function findByUmrnAndAckStatus($umrn)
    {
        return $this->newQuery()
                    ->where(Entity::UMRN, '=', $umrn)
                    ->where(Entity::ACTION, '=', Base\Action::AUTHORIZE)
                    ->with(['payment'])
                    ->firstOrFail();
    }
}
