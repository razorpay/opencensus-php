<?php

namespace RZP\Models\Dispute;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Base\ConnectionType;
use RZP\Constants\Environment;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Method as Method;
use RZP\Trace\TraceCode;
use function Aws\boolean_value;

class Repository extends Base\Repository
{
    protected $entity = 'dispute';

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = [
        Entity::STATUS             => 'sometimes|string',
        Entity::PAYMENT_ID         => 'sometimes|string|size:18',
        Entity::PHASE              => 'sometimes|string'
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::AMOUNT             => 'sometimes|integer',
        Entity::MERCHANT_ID        => 'sometimes|alpha_num',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
    ];

    public function getLatestLostOrClosedDisputeByMerchantId(string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->whereIn(Entity::STATUS, Status::getMerchantAcceptedStatuses())
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->limit(1)
            ->first();
    }

    public function getLostOrClosedDisputeInLast4MonthsByMerchantId(string $merchantId)
    {
        $fourMonthAgo = Carbon::now()->subMonths(4);

        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->whereIn(Entity::STATUS, Status::getMerchantAcceptedStatuses())
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->where(Entity::CREATED_AT, ">=", $fourMonthAgo->getTimestamp())
            ->limit(1)
            ->first();
    }

    public function getOpenNonFraudDisputes(Payment $payment)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, $payment->getId())
                    ->whereIn(Entity::STATUS, Status::getOpenStatuses())
                    ->where(Entity::PHASE, '!=', Phase::FRAUD)
                    ->get();
    }

    public function getOpenDisputeByPaymentId(string $paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, $paymentId)
                    ->whereIn(Entity::STATUS, Status::getOpenStatuses())
                    ->firstOrFail();
    }

    public function getOpenDisputesForNotification()
    {
        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        return $this->newQuery()
            ->where(Entity::STATUS, Status::OPEN)
            ->where(Entity::EMAIL_NOTIFICATION_STATUS, EmailNotificationStatus::SCHEDULED)
            ->where(Entity::EXPIRES_ON, '>', $currentTimestamp)
            ->with([Entity::PAYMENT, Entity::REASON, Entity::MERCHANT])
            ->get();
    }

    public function markOpenDisputesAsNotified(array $disputeIds)
    {
        return $this->newQuery()
            ->whereIn(Entity::ID, $disputeIds)
            ->where(Entity::STATUS, Status::OPEN)
            ->update([Entity::EMAIL_NOTIFICATION_STATUS => EmailNotificationStatus::NOTIFIED]);
    }

    public function saveOrFail($dispute, array $options = array())
    {
       $payment = $this->stripPaymentRelationIfApplicable($dispute);

       parent::saveOrFail($dispute, $options);

       $this->associatePaymentIfApplicable($dispute, $payment);
    }

    public function save($dispute, array $options = array())
    {
       $payment = $this->stripPaymentRelationIfApplicable($dispute);

       parent::save($dispute, $options);

       $this->associatePaymentIfApplicable($dispute, $payment);
    }

    public function associatePaymentIfApplicable($dispute, $payment)
    {
        if ($payment === null)
        {
            return;
        }

        $dispute->payment()->associate($payment);
    }

    protected function stripPaymentRelationIfApplicable($dispute)
    {
        $payment = $dispute->payment;

        if (($payment == null) ||
            ($payment->isExternal() === false))
        {
            return;
        }

        $dispute->payment()->dissociate();

        $dispute->setPaymentId($payment->getId());

        return $payment;
    }

    public function getDisputesByPaymentId(string $paymentId)
    {
        return $this->newQuery()
            ->where(Entity::PAYMENT_ID, $paymentId)
            ->where(Entity::STATUS, Status::LOST)
            ->get();
    }

    public function getPaymentIdsForLostDispute(int $from, int $to)
    {
        return $this->newQuery()
            ->where(Entity::STATUS, Status::LOST)
            ->where(Entity::CREATED_AT, '>=', $from)
            ->where(Entity::CREATED_AT, '<=', $to)
            ->distinct()
            ->pluck(Entity::PAYMENT_ID)
            ->toArray();
    }

    public function getMerchantIdsForRiskAnalysis(int $fromTimestamp, int $toTimestamp)
    {
        $disputeMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $disputePaymentIdColumn = $this->dbColumn(Entity::PAYMENT_ID);
        $disputeCreatedAtColumn = $this->dbColumn(Entity::CREATED_AT);
        $disputePhaseColumn = $this->dbColumn(Entity::PHASE);

        $paymentIdColumn = $this->repo->payment->dbColumn(Entity::ID);
        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment::METHOD);
        $paymentRecordSourceColumn = $this->repo->payment->dbColumn(Base\PublicEntity::RECORD_SOURCE);

        $useTiDBSourceApiFilter = false;

        $query = $this->newQuery();

        // Adding record_source filter for TiDB query only in prod
        // This check is to not break the query in lower environments because the column doesn't exist
        if ($this->app['env'] === Environment::PRODUCTION)
        {
            $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $query = $this->newQueryWithConnection($connectionType);

            $useTiDBSourceApiFilter = true;
        }

        $query = $query
            ->select($disputeMerchantIdColumn)
            ->join(Table::PAYMENT, $disputePaymentIdColumn, '=', $paymentIdColumn)
            ->where(function ($q) use ($paymentMethodColumn, $disputePhaseColumn)
            {
                $q->where(function ($qq) use ($paymentMethodColumn, $disputePhaseColumn)
                {
                    $qq->where($paymentMethodColumn, '=', Method::CARD)
                        ->where($disputePhaseColumn, '!=', Phase::RETRIEVAL);
                })
                ->orWhere($paymentMethodColumn, '!=', Method::CARD);
            })
            ->where($disputeCreatedAtColumn, '>=', $fromTimestamp)
            ->where($disputeCreatedAtColumn, '<', $toTimestamp);

        if ($useTiDBSourceApiFilter === true)
        {
            $query = $query->where($paymentRecordSourceColumn, '=', Base\Constants::RECORD_SOURCE_API);
        }

        return $query
            ->distinct()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function getCreatedAtFromDisputeId($disputeId): array
    {
        $createdAtCol = $this->dbColumn(Entity::CREATED_AT);
        $idCol        = $this->dbColumn(Entity::ID);

        return $this->newQuery()
            ->select($createdAtCol)
            ->where($idCol, $disputeId)
            ->get()
            ->toArray();
    }

    private function getMerchantDisputedPaymentsQueryForRiskAnalysis(string $merchantId, int $fromTimestamp, int $toTimestamp, $query)
    {
        $disputeMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $disputePaymentIdColumn = $this->dbColumn(Entity::PAYMENT_ID);
        $disputeCreatedAtColumn = $this->dbColumn(Entity::CREATED_AT);
        $disputePhaseColumn = $this->dbColumn(Entity::PHASE);

        $paymentIdColumn = $this->repo->payment->dbColumn(Entity::ID);
        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment::METHOD);

        return $query->select($disputePaymentIdColumn)
            ->from(Table::DISPUTE)
            ->join(Table::PAYMENT, $disputePaymentIdColumn, '=', $paymentIdColumn)
            ->where(function ($q) use ($paymentMethodColumn, $disputePhaseColumn)
            {
                $q->where(function ($qq) use ($paymentMethodColumn, $disputePhaseColumn)
                {
                    $qq->where($paymentMethodColumn, '=', Method::CARD)
                        ->where($disputePhaseColumn, '!=', Phase::RETRIEVAL);
                })
                ->orWhere($paymentMethodColumn, '!=', Method::CARD);
            })
            ->where($disputeMerchantIdColumn, '=', $merchantId)
            ->where($disputeCreatedAtColumn, '>=', $fromTimestamp)
            ->where($disputeCreatedAtColumn, '<', $toTimestamp)
            ->distinct();
    }

    public function getMerchantDisputedPaymentsCountForRiskAnalysis(string $merchantId, int $fromTimestamp, int $toTimestamp)
    {
        $disputePaymentIdColumn = $this->dbColumn(Entity::PAYMENT_ID);

        $paymentRecordSourceColumn = $this->repo->payment->dbColumn(Base\PublicEntity::RECORD_SOURCE);

        $useTiDBSourceApiFilter = false;

        $query = $this->newQuery();

        // Adding record_source filter for TiDB query only in prod
        // This check is to not break the query in lower environments because the column doesn't exist
        if ($this->app['env'] === Environment::PRODUCTION)
        {
            $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $query = $this->newQueryWithConnection($connectionType);

            $useTiDBSourceApiFilter = true;
        }

        $this->getMerchantDisputedPaymentsQueryForRiskAnalysis($merchantId, $fromTimestamp, $toTimestamp, $query);

        if ($useTiDBSourceApiFilter === true)
        {
            $query = $query->where($paymentRecordSourceColumn, '=', Base\Constants::RECORD_SOURCE_API);
        }

        return $query->count($disputePaymentIdColumn);
    }

    public function getMerchantDisputedPaymentsGmvForRiskAnalysis(string $merchantId, int $fromTimestamp, int $toTimestamp)
    {
        $paymentIdColumn = $this->repo->payment->dbColumn(Entity::ID);
        $paymentBaseAmountColumn = $this->repo->payment->dbColumn(Payment::BASE_AMOUNT);
        $paymentRecordSourceColumn = $this->repo->payment->dbColumn(Base\PublicEntity::RECORD_SOURCE);

        $disputeRepo = $this;

        $useTiDBSourceApiFilter = false;

        $query = $this->repo->payment->newQuery();

        // Adding record_source filter for TiDB query only in prod
        // This check is to not break the query in lower environments because the column doesn't exist
        if ($this->app['env'] === Environment::PRODUCTION)
        {
            $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $query = $this->repo->payment->newQueryWithConnection($connectionType);

            $useTiDBSourceApiFilter = true;
        }

        $query = $query
            ->whereIn($paymentIdColumn, function($query) use ($merchantId, $fromTimestamp, $toTimestamp, $disputeRepo)
            {
                $disputeRepo->getMerchantDisputedPaymentsQueryForRiskAnalysis($merchantId, $fromTimestamp, $toTimestamp, $query);
            });

        if ($useTiDBSourceApiFilter === true)
        {
            $query = $query->where($paymentRecordSourceColumn, '=', Base\Constants::RECORD_SOURCE_API);
        }

        return $query->sum($paymentBaseAmountColumn);
    }

    public function getMerchantDisputedPaymentsCountbyPhaseForRiskAnalysis(string $merchantId, int $fromTimestamp, int $toTimestamp, array $phases = [])
    {
        if (empty($phases) === true)
        {
            return 0;
        }

        return $this->newQuery()
            ->select(Entity::PAYMENT_ID)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->whereIn(Entity::PHASE, $phases)
            ->where(Entity::CREATED_AT, '>=', $fromTimestamp)
            ->where(Entity::CREATED_AT, '<', $toTimestamp)
            ->distinct()
            ->count(Entity::PAYMENT_ID);
    }

    public function getCountForFetchMultiple($params)
    {
        $this->validateFetchParams($params);

        $query = $this->newQueryWithConnection($this->getDataWarehouseConnection());

        $merchantId = $this->merchant->getId();

        $this->buildQueryWithParams($query, $params);

        $query = $query->merchantId($merchantId);

        $count = $query->count();

        return ['count' => $count];
    }

    protected function addQueryParamGateway($query, $params)
    {
        $disputePaymentIdColumn = $this->dbColumn(Entity::PAYMENT_ID);

        $paymentIdColumn = $this->repo->payment->dbColumn(Entity::ID);

        $dbColumn = $this->repo->payment->dbColumn(Payment::GATEWAY);

        $param = $params[Payment::GATEWAY];

        $query->join(Table::PAYMENT, $disputePaymentIdColumn, '=', $paymentIdColumn)
            ->select(Table::DISPUTE.'.*')
            ->where($dbColumn, $param);
    }


    protected function addQueryParamDeductionReversalAtSet($query, $params)
    {
        if (boolval($params[Entity::DEDUCTION_REVERSAL_AT_SET]) === false)
        {
            return;
        }

        $query->where(Entity::DEDUCTION_REVERSAL_AT, '!=', null)
              ->where(Entity::STATUS, '=', Status::UNDER_REVIEW);
    }

    protected function addQueryParamDeductionReversalAtFrom($query, $params)
    {
        $dbColumn = $this->dbColumn(Entity::DEDUCTION_REVERSAL_AT);

        $param = $params[Entity::DEDUCTION_REVERSAL_AT_FROM];

        $query->where($dbColumn, '>=', $param)
              ->where(Entity::STATUS, '=', Status::UNDER_REVIEW);
    }

    protected function addQueryParamDeductionReversalAtTo($query, $params)
    {
        $dbColumn = $this->dbColumn(Entity::DEDUCTION_REVERSAL_AT);

        $param = $params[Entity::DEDUCTION_REVERSAL_AT_TO];

        $query->where($dbColumn, '<', $param)
            ->where(Entity::STATUS, '=', Status::UNDER_REVIEW);
    }

    protected function addQueryParamInternalRespondByFrom($query, $params)
    {
        $dbColumn = $this->dbColumn(Entity::INTERNAL_RESPOND_BY);

        $param = $params[Entity::INTERNAL_RESPOND_BY_FROM];

        $query->where($dbColumn, '>=', $param);
    }

    protected function addQueryParamInternalRespondByTo($query, $params)
    {
        $dbColumn = $this->dbColumn(Entity::INTERNAL_RESPOND_BY);

        $param = $params[Entity::INTERNAL_RESPOND_BY_TO];

        $query->where($dbColumn, '<=', $param);
    }

    protected function addQueryParamOrderByInternalRespond($query, $params)
    {
        $dbColumn = $this->dbColumn(Entity::INTERNAL_RESPOND_BY);

        $param = $params[Entity::ORDER_BY_INTERNAL_RESPOND] ?? false;

        if ((bool)$param === false)
        {
            return;
        }

        $query->orderBy(Entity::INTERNAL_RESPOND_BY, 'asc');
    }

    protected function addQueryParamGatewayDisputeSource($query, $params)
    {
        $dbColumn = $this->dbColumn(Entity::GATEWAY_DISPUTE_ID);

        $param = $params[Entity::GATEWAY_DISPUTE_SOURCE];

        if ($param === Constants::GATEWAY_DISPUTE_SOURCE_CUSTOMER)
        {
            $query->where($dbColumn,'like','DISPUTE%');
        }
        elseif ($param === Constants::GATEWAY_DISPUTE_SOURCE_NETWORK)
        {
            $query->where($dbColumn, 'not like', 'DISPUTE%');
        }
    }

    /**
     * This query fetches all merchantIds with their corresponding number of
     * disputes lost/closed after minCreatedAt timestamp
     *
     * @param int $minCreatedAt
     *
     * @return array Sample Output: ["mid1" => 1, "mid2" => 10, "mid3" => 4]
     */
    public function getCountOfLostOrClosedDisputesForMerchants(int $minCreatedAt): array
    {
        $result = $this->newQueryWithConnection($this->getSlaveConnection())
            ->selectRaw(Entity::MERCHANT_ID . ', COUNT(*) AS disputes_count')
            ->whereIn(Entity::STATUS, Status::getMerchantAcceptedStatuses())
            ->where(Entity::CREATED_AT, ">=", $minCreatedAt)
            ->groupby(Entity::MERCHANT_ID)
            ->pluck('disputes_count' , Entity::MERCHANT_ID);

        return $result->toArray();
    }

    public function getDisputesForDeductionReversal()
    {
         return $this->newQuery()
                      ->where(Entity::STATUS, '=', Status::UNDER_REVIEW)
                      ->where(Entity::INTERNAL_STATUS, '=', InternalStatus::REPRESENTED)
                      ->where(Entity::DEDUCTION_REVERSAL_AT, '<', time())
                      ->where(Entity::DEDUCT_AT_ONSET, '=', true)
                      ->where(Entity::DEDUCTION_SOURCE_TYPE, '=', 'adjustment')
                      ->get();
    }

    public function getDisputesForAdjustmentIds($merchantId, $adjustmentIds)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::DEDUCTION_SOURCE_TYPE, 'adjustment')
            ->whereIn(Entity::DEDUCTION_SOURCE_ID, $adjustmentIds)
            ->get();
    }

    public function getNonDaoLostAndWonDisputes($merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->whereNotIn(Entity::STATUS, [Status::LOST, Status::WON])
            ->where(Entity::DEDUCT_AT_ONSET, '=', false)
            ->first();
    }
}
