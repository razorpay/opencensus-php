<?php

namespace RZP\Models\Partner\Activation;


use RZP\Models\Base;
use RZP\Constants\Es;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::MERCHANT_ID,
        Entity::ACTIVATION_STATUS,
        Entity::REVIEWER_ID,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
        Entity::SUBMITTED_AT,
    ];

    protected $merchantIndexedFields = [
        MerchantEntity::ID,
        MerchantEntity::NAME,
        MerchantEntity::EMAIL,
    ];

    protected $queryFields = [
        E::MERCHANT. '.' . MerchantEntity::ID,
        E::MERCHANT. '.' . MerchantEntity::NAME,
        E::MERCHANT. '.' . MerchantEntity::EMAIL,
    ];

    protected $esFetchParams = [
        self::QUERY,
        self::SEARCH_HITS,
        Entity::MERCHANT_ID,
        Entity::ACTIVATION_STATUS,
        DetailEntity::REVIEWER_ID,
    ];

    protected $sortBySubmittedAtAsc = false;

    public function getMerchantIndexedFields()
    {
        return $this->merchantIndexedFields;
    }

    public function buildQueryForMerchantId(array & $query, string $value)
    {
        $attribute = Entity::MERCHANT_ID;

        $this->buildQueryForFieldDefaultImpl($query, $attribute, $value);
    }

    public function getSortParameter(): array
    {
        if ($this->sortBySubmittedAtAsc === false)
        {
            return parent::getSortParameter();
        }

        $submittedAtAttr = E::PARTNER_ACTIVATION . '.' . DetailEntity::SUBMITTED_AT;

        return [
            Es::_SCORE => [
                Es::ORDER => Es::DESC,
            ],
            $submittedAtAttr => [
                Es::ORDER => Es::ASC,
            ],
        ];
    }

    public function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }

    public function bulkUpdate(array $documents): array
    {
        $params = [];

        $documents = array_values(array_filter($documents));

        if (empty($documents) === true)
        {
            return [];
        }

        foreach($documents as $document)
        {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_type'  => $this->typeName,
                    '_id'    => $document[Entity::MERCHANT_ID],
                ]
            ];

            $params['body'][] = $document;
        }

        $res = $this->esDao->bulkUpdate($params);

        $this->checkForBulkUpdateOperationErrors($params, $res);

        return $res;
    }
}
