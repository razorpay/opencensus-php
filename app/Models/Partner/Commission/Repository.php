<?php

namespace RZP\Models\Partner\Commission;

use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Partner\Config\CommissionModel;
use RZP\Models\Base\Repository as BaseRepository;
use Carbon\Carbon;
use RZP\Constants\Entity as E;
use RZP\Models\Transaction\Entity as TransactionEntity;

class Repository extends BaseRepository
{
    protected $entity = 'commission';

    protected $merchantIdRequiredForMultipleFetch = false;

    protected $proxyFetchParamRules = [
        Entity::ID                => 'filled|string|size:19',
        Entity::TYPE              => 'filled|string|in:'. Type::IMPLICIT .','. Type::EXPLICIT,
        Entity::STATUS            => 'filled|string|custom',
        Entity::MODEL             => 'filled|string|custom',
        Entity::SOURCE_ID         => 'sometimes|string|min:14|max:19',
        Entity::PARTNER_ID        => 'required|string|size:14',
        Entity::MERCHANT_ID       => 'sometimes|string|min:14|max:18',
        Entity::SOURCE_TYPE       => 'filled|string',
        Entity::PARTNER_CONFIG_ID => 'sometimes|string|size:14',
        Entity::TRANSACTION_ID    => 'sometimes|string|size:14',
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
     * @param $attribute
     * @param $model
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateModel($attribute, $model)
    {
        CommissionModel::validate($model);
    }

    /**
     * Fetch all commissionIds which are yet to be captured
     *
     * @param string $partnerId
     *
     * @return array
     */
    public function getCommissionIdsToBeCaptured(string $partnerId): array
    {
        $commissionIds = $this->newQuery()
                              ->where(Entity::STATUS, Status::CREATED)
                              ->where(Entity::PARTNER_ID, $partnerId)
                              ->select(Entity::ID)
                              ->get()
                              ->getPublicIds();

        return $commissionIds;
    }

    public function findMultipleByPublicIds(array $ids): Base\PublicCollection
    {
        $ids = Entity::verifyIdAndStripSignMultiple($ids);

        return $this->newQuery()
                    ->findManyOrFailPublic($ids);
    }

    public function fetchAggregateFeesAndTaxForInvoice(string $partnerId, int $start, int $end): array
    {
        // fetching aggregate Tax and Fee for payment (primary) commissions
        $primaryQuery = $this->newQuery()
                             ->selectRaw('SUM(' . Entity::TAX . ') AS tax, SUM(' . Entity::FEE . ') AS fee')
                             ->where(Entity::PARTNER_ID, $partnerId)
                             ->where(Entity::SOURCE_TYPE, Constants::PAYMENT)
                             ->whereBetween(Entity::CREATED_AT, [$start, $end]);

        $nonZeroTaxPrimaryQuery = clone $primaryQuery;

        $zeroTaxDetailsPrimary    = $primaryQuery->where(Entity::TAX, 0)->first();
        $nonZeroTaxDetailsPrimary = $nonZeroTaxPrimaryQuery->where(Entity::TAX, '>', 0)->first();

        // fetching aggregate Tax and Fee for payout (banking) commissions
        $bankingQuery = $this->newQuery()
                             ->selectRaw('SUM(' . Entity::TAX . ') AS tax, SUM(' . Entity::FEE . ') AS fee')
                             ->where(Entity::PARTNER_ID, $partnerId)
                             ->where(Entity::SOURCE_TYPE, Constants::PAYOUT)
                             ->whereBetween(Entity::CREATED_AT, [$start, $end]);

        $nonZeroTaxBankingQuery = clone $bankingQuery;

        $zeroTaxDetailsBanking    = $bankingQuery->where(Entity::TAX, 0)->first();
        $nonZeroTaxDetailsBanking = $nonZeroTaxBankingQuery->where(Entity::TAX, '>', 0)->first();

        // taxable and non_taxable components of payment and payout commissions
        return [
            'zero_tax_primary'    => $zeroTaxDetailsPrimary,
            'zero_tax_banking'    => $zeroTaxDetailsBanking,
            'nonzero_tax_primary' => $nonZeroTaxDetailsPrimary,
            'nonzero_tax_banking' => $nonZeroTaxDetailsBanking,
        ];
    }

    public function isEarningsPresentForPartner(string $partnerId)
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_TYPE, Constants::PAYMENT)
                    ->where(Entity::PARTNER_ID, $partnerId)
                    ->exists();
    }

    public function isCommissionPayoutPresentForPartner(string $partnerId)
    {
        $commissionIdColumn = $this->dbColumn(Entity::ID);
        $entityIdColumn = $this->repo->transaction->dbColumn(TransactionEntity::ENTITY_ID);
        $typeColumn = $this->repo->transaction->dbColumn(Entity::TYPE);
        $onHoldColumn = $this->repo->transaction->dbColumn(TransactionEntity::ON_HOLD);

        return $this->newQuery()
                    ->join(Table::TRANSACTION, $entityIdColumn, '=', $commissionIdColumn)
                    ->where(Entity::SOURCE_TYPE, Constants::PAYMENT)
                    ->where(Entity::PARTNER_ID, $partnerId)
                    ->where($typeColumn, \RZP\Models\Transaction\Type::COMMISSION)
                    ->where($onHoldColumn, false)
                    ->exists();
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
        // merchant id can be searched using acc_{id} in case of partners
        // id is not picked up parent function
        //

        $params = [Entity::SOURCE_ID, Entity::MERCHANT_ID, Entity::ID];

        foreach ($params as $param)
        {
            if (empty($input[$param]) === false)
            {
                $input[$param] = PublicEntity::stripDefaultSign($input[$param]);
            }
        }

        parent::modifyFetchParams($input);
    }

    public function saveOrFail($commission, array $options = array())
    {
        $paymentSource = $this->stripPaymentSourceRelationIfApplicable($commission);

        parent::saveOrFail($commission, $options);

        $this->associatePaymentSourceIfApplicable($commission, $paymentSource);
    }

    protected function stripPaymentSourceRelationIfApplicable($commission)
    {
        $source = $commission->source;

        if (($source === null) or
            ($source->getEntityName() !== E::PAYMENT))
        {
            return;
        }

        $commission->source()->dissociate();

        $commission->setAttribute(Entity::SOURCE_ID, $source->getId());

        $commission->setAttribute(Entity::SOURCE_TYPE, E::PAYMENT);

        return $source;
    }

    public function associatePaymentSourceIfApplicable($commission, $payment)
    {
        if ($payment === null)
        {
            return;
        }

        $commission->source()->associate($payment);
    }
}
