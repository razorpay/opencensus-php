<?php

namespace RZP\Models\Partner\KycAccessState;

use RZP\Base;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Core as MerchantCore;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_ID => 'required|max:14',
    ];

    protected static $tokenRules = [
        Entity::ENTITY_ID     => 'required',
        Entity::PARTNER_ID    => 'required',
        Entity::APPROVE_TOKEN => 'required_without:reject_token',
        Entity::REJECT_TOKEN  => 'required_without:approve_token',
    ];

    protected static $revokeAccessRules = [
        Entity::PARTNER_ID => 'required|string|size:14',
    ];

    protected static $tokenValidators = [
        'token'
    ];

    public function validateMerchantReferredByPartner($partnerId, $submerchantId)
    {
        if ((new MerchantCore)->isMerchantReferredByPartner($submerchantId, $partnerId) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER);
        }
    }

    protected function validateToken(array $input)
    {
        if (isset($input[Entity::REJECT_TOKEN]) === true  and isset($input[Entity::APPROVE_TOKEN]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(PublicErrorDescription::BAD_REQUEST_BOTH_TOKENS_PRESENT);
        }
    }
}
