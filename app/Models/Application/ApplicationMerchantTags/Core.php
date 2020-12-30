<?php

namespace RZP\Models\Application\ApplicationMerchantTags;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{

    public function create(array $input): Entity
    {
        $appMerchantTagEntity = $this->repo->application_merchant_tag->getMerchantTag($input[Entity::MERCHANT_ID]);

        if (empty($appMerchantTagEntity) === false)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_DUPLICATE_MERCHANT_TAG,
                null,
                $input
            );
        }

        $appMerchantTagEntity = new Entity;

        $merchant = $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $appMerchantTagEntity->merchant()->associate($merchant);

        $appMerchantTagEntity->setTag(strtolower($input[Entity::TAG]));

        $this->repo->saveOrFail($appMerchantTagEntity);

        return $appMerchantTagEntity;
    }

    public function update(Entity $appMerchantTagEntity, array $input): Entity
    {
        $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $appMerchantTagEntity->setTag(strtolower($input[Entity::TAG]));

        $this->repo->saveOrFail($appMerchantTagEntity);

        return $appMerchantTagEntity;
    }
}
