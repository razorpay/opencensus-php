<?php

namespace RZP\Models\Merchant\InternationalEnablement\Document;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'international_enablement_document';

    protected $adminFetchParamRules = [
        Entity::INTERNATIONAL_ENABLEMENT_DETAIL_ID => 'sometimes|string|size:14',
    ];

    public function fetchDocumentByMerchantIdAndIEDetailIdAndType($merchantId, $IEDetailId, $type)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::INTERNATIONAL_ENABLEMENT_DETAIL_ID, $IEDetailId)
            ->where(Entity::TYPE, $type)
            ->first();
    }

    public function fetchOtherDocumentByMerchantIdAndIEDetailIdAndCustomType($merchantId, $IEDetailId, $customType)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::INTERNATIONAL_ENABLEMENT_DETAIL_ID, $IEDetailId)
            ->where(Entity::CUSTOM_TYPE, $customType)
            ->first();
    }
}
