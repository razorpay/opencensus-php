<?php

namespace RZP\Models\Partner\Commission;

use RZP\Base\BuilderEx;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected $entity = 'commission';

    protected $merchantIdRequiredForMultipleFetch = false;

    protected $proxyFetchParamRules = [
        Entity::STATUS            => 'filled|string|custom',
        Entity::SOURCE_ID         => 'sometimes|string|min:14|max:19',
        Entity::PARTNER_ID        => 'required|string|size:14',
        Entity::MERCHANT_ID       => 'sometimes|string|min:14|max:18',
        Entity::SOURCE_TYPE       => 'filled|string',
        Entity::PARTNER_CONFIG_ID => 'sometimes|string|size:14',
        self::EXPAND . '.*'       => 'sometimes|in:source.merchant',
    ];

    protected $adminFetchParamRules = [
        Entity::PARTNER_ID  => 'sometimes|string|size:14',
    ];

    /**
     * @param $attribute
     * @param $status
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateStatus($attribute, $status)
    {
        Status::validateStatus($status);
    }

    /**
     * Override this function to include relations when indexing entity
     *
     * @param BuilderEx $query
     */
    protected function modifyQueryForIndexing(BuilderEx $query)
    {
        $merchantSelector = function ($query)
                            {
                                $fields = $this->esRepo->getMerchantFields();

                                $query->select($fields);
                            };

        $relations = [
            Entity::SOURCE_MERCHANT => $merchantSelector,
        ];

        $query->with($relations);
    }

    protected function serializeForIndexing(PublicEntity $entity): array
    {
        $serialized = parent::serializeForIndexing($entity);

        $serialized[Entity::MERCHANT] = [
            Entity::ID => $entity->source->merchant->getId(),
        ];

        return $serialized;
    }

    /**
     * Modify input parameters to strip public signs
     *
     * @param array $input
     */
    protected function modifyFetchParams(array & $input)
    {
        //
        // the parent function strips sign from each parameter only if the param is a valid entity
        // since source is not a valid entity, we are stripping the sign for source_id here
        //
        if (empty($input[Entity::SOURCE_ID]) === false)
        {
            $input[Entity::SOURCE_ID] = PublicEntity::stripDefaultSign($input[Entity::SOURCE_ID]);
        }

        // merchant id can be searched using acc_{id} in case of partners
        if (empty($input[Entity::MERCHANT_ID]) === false)
        {
            $input[Entity::MERCHANT_ID] = PublicEntity::stripDefaultSign($input[Entity::MERCHANT_ID]);
        }

        parent::modifyFetchParams($input);
    }
}
