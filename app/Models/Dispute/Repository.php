<?php

namespace RZP\Models\Dispute;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Method as Method;

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

        return $this->newQuery()
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
            ->where($disputeCreatedAtColumn, '<', $toTimestamp)
            ->distinct()
            ->pluck(Entity::MERCHANT_ID)
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

        $query = $this->newQuery();

        $this->getMerchantDisputedPaymentsQueryForRiskAnalysis($merchantId, $fromTimestamp, $toTimestamp, $query);

        return $query->count($disputePaymentIdColumn);
    }

    public function getMerchantDisputedPaymentsGmvForRiskAnalysis(string $merchantId, int $fromTimestamp, int $toTimestamp)
    {
        $paymentIdColumn = $this->repo->payment->dbColumn(Entity::ID);
        $paymentBaseAmountColumn = $this->repo->payment->dbColumn(Payment::BASE_AMOUNT);

        $disputeRepo = $this;

        return $this->repo->payment->newQuery()
            ->whereIn($paymentIdColumn, function($query) use ($merchantId, $fromTimestamp, $toTimestamp, $disputeRepo)
            {
                $disputeRepo->getMerchantDisputedPaymentsQueryForRiskAnalysis($merchantId, $fromTimestamp, $toTimestamp, $query);
            })
            ->sum($paymentBaseAmountColumn);
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
}
