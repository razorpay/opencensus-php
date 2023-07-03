<?php

namespace RZP\Models\Merchant\Acs\ParityChecker;

use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;
use RZP\Models\Merchant\Acs\ParityChecker\Entity\MerchantDetail;
use RZP\Models\Merchant\Acs\ParityChecker\Entity\MerchantDocument;
use RZP\Models\Merchant\Acs\ParityChecker\Entity\MerchantWebsite;
use RZP\Models\Merchant\Acs\ParityChecker\Entity\MerchantEmail;
use RZP\Models\Merchant\Acs\ParityChecker\Entity\MerchantBusinessDetail;
use RZP\Models\Merchant\Acs\ParityChecker\Entity\Stakeholder;


class Factory
{
    function __construct()
    {

    }

    function getEntityParityCheckerClass(string $entity): string
    {
        $entityParityCheckerClass = match ($entity) {
            Constant::MERCHANT_WEBSITE => MerchantWebsite::class,
            Constant::MERCHANT_EMAIL => MerchantEmail::class,
            Constant::MERCHANT_DOCUMENT => MerchantDocument::class,
            Constant::MERCHANT_BUSINESS_DETAIL => MerchantBusinessDetail::class,
            Constant::STAKEHOLDER => Stakeholder::class,
            Constant::MERCHANT_DETAIL => MerchantDetail::class,
            default => throw new BaseException('parity checker class is not defined for entity ' . $entity,
                ErrorCode::ASV_INTERNAL_PARITY_CHECKER_ERROR),
        };

        return $entityParityCheckerClass;
    }
}
