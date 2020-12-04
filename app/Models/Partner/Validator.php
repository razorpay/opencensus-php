<?php

namespace RZP\Models\Partner;

use RZP\Base;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Entity;
use RZP\Exception\BadRequestException;

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
}
