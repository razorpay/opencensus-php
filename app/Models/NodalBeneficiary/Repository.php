<?php

namespace RZP\Models\NodalBeneficiary;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'nodal_beneficiary';

    protected $appFetchParamRules = [
        Entity::CHANNEL             => 'filled|string|max:8|custom',
        Entity::MERCHANT_ID         => 'filled|string|size:14',
        Entity::BANK_ACCOUNT_ID     => 'filled|string|size:14',
        Entity::REGISTRATION_STATUS => 'filled|string|max:40|custom',
        Entity::BENEFICIARY_CODE    => 'sometimes|filled|nullable|string|max:30',
    ];

    /**
     * @param string $bankAccountId
     * @param string $channel
     * @return Entity
     */
    public function fetchBeneficiaryDetailsForChannel(string $bankAccountId, string $channel): Entity
    {
        return $this->newQuery()
                    ->where(Entity::BANK_ACCOUNT_ID, $bankAccountId)
                    ->where(Entity::CHANNEL, $channel)
                    ->withTrashed()
                    ->firstorFail();
    }

}
