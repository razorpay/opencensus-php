<?php

namespace RZP\Models\Partner\Activation;

use RZP\Base\Common;
use RZP\Base\BuilderEx;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Base\EsRepository;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Base\Repository as BaseRepository;
use RZP\Exception\BadRequestValidationFailureException;


class Repository  extends BaseRepository
{
    use RepositoryUpdateTestAndLive
    {
        saveOrFail as saveOrFailTestAndLive;
    }

    protected $entity = 'partner_activation';

    protected $proxyFetchParamRules = [
        Entity::MERCHANT_ID       => 'sometimes|string|size:14',
    ];

    protected $adminFetchParamRules = [
        EsRepository::QUERY         => 'filled|string|min:2|max:100',
        EsRepository::SEARCH_HITS   => 'filled|boolean',
        Entity::MERCHANT_ID         => 'sometimes|string|max:14',
        Entity::ACTIVATION_STATUS   => 'filled|custom',
        Entity::REVIEWER_ID         => 'sometimes|string|max:14',
    ];

    public function saveOrFail($partnerActivation, array $options = array())
    {
        $this->saveOrFailTestAndLive($partnerActivation, $options);

        $this->syncToEsLiveAndTest($partnerActivation, EsRepository::UPDATE);
    }

    protected function validateActivationStatus($attribute, $value)
    {
        $validActivationStatuses = array_keys(Constants::NEXT_ACTIVATION_STATUSES_MAPPING);

        if (in_array($value, $validActivationStatuses, true) === false)
        {
            throw new BadRequestValidationFailureException(Detail\Validator::INVALID_STATUS_MESSAGE);
        }
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }

    public function findManyForIndexing(
        string $afterId = null,
        int $take = 100,
        int $createdAtStart = null,
        int $createdAtEnd = null): array
    {
        $query = $this->newQuery();

        $idCol        = $this->dbColumn(Entity::MERCHANT_ID);
        $createdAtCol = $this->dbColumn(Common::CREATED_AT);

        if ($afterId !== null)
        {
            $query->where($idCol, '>', $afterId);
        }

        if ($createdAtStart !== null)
        {
            $query->where($createdAtCol, '>=', $createdAtStart);
        }

        if ($createdAtEnd !== null)
        {
            $query->where($createdAtCol, '<=', $createdAtEnd);
        }

        $this->modifyQueryForIndexing($query);

        $collection = $query->take($take)->orderBy($idCol, 'asc')->get();

        return array_map(
            function ($v)
            {
                if ($v->merchant_id === '')
                {
                    return null;
                }

                return $this->serializeForIndexing($v);
            },
            $collection->all());
    }

    protected function modifyQueryForIndexing(BuilderEx $query)
    {
        $merchantSelector = function ($query)
        {
            $fields = $this->esRepo->getMerchantIndexedFields();

            $query->select($fields);
        };

        $with = [
            E::MERCHANT => $merchantSelector
        ];

        $query->with($with);
    }

    protected function serializeForIndexing(PublicEntity $entity): array
    {
        $serialized = parent::serializeForIndexing($entity);

        $merchantDetail = $entity->merchantDetail;

        $merchant = $merchantDetail->merchant;

        $serialized[E::MERCHANT] = $merchant ? $merchant->toArray() : [];

        return $serialized;
    }

    public function fetchPartnersWithIncompleteKyc()
    {
        $partnersWithIncompleteKyc =  $this->newQuery()
                                           ->select(Entity::MERCHANT_ID)
                                           ->where(Entity::ACTIVATION_STATUS,'=', Constants::NEEDS_CLARIFICATION)
                                           ->orWhere(Entity::ACTIVATION_STATUS,'=',null)
                                           ->get();

        return $partnersWithIncompleteKyc->pluck(Entity::MERCHANT_ID)->toArray();
    }
}
