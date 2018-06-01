<?php

namespace RZP\Models\State;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Request as MerchantRequest;

class Repository extends Base\Repository
{
    const VALID_ENTITY_TYPE = [
        E::DISPUTE,
        E::WORKFLOW_ACTION,
    ];

    protected $entity = 'state';

    protected $appFetchParamRules = [
        Entity::ADMIN_ID    => 'filled|string|size:14',
        Entity::MERCHANT_ID => 'filled|string|size:14',
        Entity::ENTITY_ID   => 'filled|string|size:14',
        Entity::ENTITY_TYPE => 'filled|string|max:100|custom',
    ];

    protected function validateEntityType($attribute, $value)
    {
        if (in_array($value, self::VALID_ENTITY_TYPE, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MORPHED_ENTITY_INVALID,
                Entity::ENTITY_TYPE,
                [
                    Entity::ENTITY_TYPE => $value
                ]);
        }
    }

    public function findLastMerchantRequestState(MerchantRequest\Entity $request)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $request->getId())
                    ->where(Entity::NAME, $request->getStatus())
                    ->firstOrFailPublic();
    }
}
