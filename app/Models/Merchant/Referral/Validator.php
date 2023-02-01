<?php

namespace RZP\Models\Merchant\Referral;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Entity as MerchantEntity;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::REF_CODE => 'required|string|max:14',
        Entity::URL      => 'sometimes|string|nullable',
        Entity::PRODUCT  => 'required|string|in:primary,banking,capital',
    ];

    protected static $editRules = [
        Entity::URL => 'sometimes|string|nullable',
    ];

    /**
     * @param MerchantEntity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateForReferral(MerchantEntity $merchant)
    {
        $isReseller = $merchant->isResellerPartner();

        $isAggregator = $merchant->isAggregatorPartner();

        $isFullyManaged = $merchant->isFullyManagedPartner();

        if (($isReseller === false) and ($isAggregator === false) and ($isFullyManaged === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER);
        }

        return;
    }
}
