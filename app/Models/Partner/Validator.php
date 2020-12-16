<?php

namespace RZP\Models\Partner;

use RZP\Base;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Entity;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApp;

class Validator extends Base\Validator
{
    /**
     * @param Merchant\Entity $partner

     * @return void
     * @throws BadRequestException
     */
    public function validateIfAggregatorOrFullyManagedPartner(Merchant\Entity $partner)
    {
        $partnerType = $partner->getPartnerType();

        if (($partnerType !== Merchant\Constants::AGGREGATOR) and ($partnerType !== Merchant\Constants::FULLY_MANAGED))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                [
                    Entity::PARTNER_ID   => $partner->getId(),
                    Entity::PARTNER_TYPE => $partnerType,
                ]
            );
        }
    }

    /**
     * @param string $fromAppType
     * @param string $toAppType
     *
     * @return void
     * @throws BadRequestException
     */
    public function validateAppTypeChange(string $fromAppType, string $toAppType)
    {
        $allowedAppTypes = [MerchantApp::REFERRED, MerchantApp::MANAGED];

        if ((in_array($fromAppType, $allowedAppTypes) === false) or
            (in_array($toAppType, $allowedAppTypes) === false) or
            ($fromAppType === $toAppType))
        {
            throw new BadRequestException (
                ErrorCode::BAD_REQUEST_INVALID_APPLICATION_TYPE,
                [
                    'from_app_type' => $fromAppType,
                    'to_app_type'   => $toAppType,
                ]
            );
        }
    }
}
