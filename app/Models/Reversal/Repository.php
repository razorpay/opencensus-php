<?php

namespace RZP\Models\Reversal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Reversal;
use RZP\Constants\Entity as E;
use RZP\Models\Payment\Refund;
use RZP\Exception\LogicException;
use RZP\Models\Pricing\Calculator;
use Illuminate\Database\Query\JoinClause;
use RZP\Models\Merchant\Invoice\Type as InvoiceType;

class Repository extends Base\Repository
{
    protected $entity = 'reversal';

    protected $entityFetchParamRules = [
        Entity::ENTITY_TYPE     => 'sometimes|string|max:255',
        Entity::ENTITY_ID       => 'sometimes|string|size:14',
    ];

    protected $appFetchParamRules = [
        Entity::TRANSACTION_ID  => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num|size:14',
    ];

    /**
     * fetches reversals for a LA transfer by joining refunds
     *
     * @param string $transferId
     * @param string $merchantId
     *
     * @return array|mixed
     */
    public function fetchLaReversalsOfTransfer(string $transferId, string $merchantId)
    {
        $reversalColumns = $this->dbColumn('*');

        $reversalId = $this->repo->refund->dbColumn(Refund\Entity::REVERSAL_ID);

        $reversalsId = $this->repo->reversal->dbColumn(Reversal\Entity::ID);

        $refundNotes = $this->repo->refund->dbColumn(Refund\Entity::NOTES);

        $refundsTable = $this->repo->refund->getTableName();

        $reversalEntityType = $this->repo->reversal->dbColumn(Reversal\Entity::ENTITY_TYPE);

        $reversalEntityId = $this->repo->reversal->dbColumn(Reversal\Entity::ENTITY_ID);

        $refundMerchantId = $this->repo->refund->dbColumn(Refund\Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->join($refundsTable, $reversalId, '=', $reversalsId)
                    ->select($reversalColumns, $refundNotes)
                    ->where($reversalEntityId, $transferId)
                    ->where($reversalEntityType, Reversal\Entity::TRANSFER)
                    ->where($refundMerchantId, $merchantId)
                    ->get();
    }

    public function fetchReversalsList($skip = 0, $take = 100, $entityType = Entity::TRANSFER)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->orderBy(Entity::ID, 'desc')
                    ->skip($skip)
                    ->take($take)
                    ->get();
    }

    public function fetchFeesAndTaxForRefundByType(
        string $merchantId,
        int $start,
        int $end,
        string $filterType)
    {
        /*
            SELECT Sum(reversals.tax) AS tax,
                   Sum(reversals.fee) AS fee
            FROM   `reversals`
                   INNER JOIN `refunds`
                           ON `reversals`.`entity_id` = `refunds`.`id`
            WHERE  `reversals`.`entity_type` = ?
                   AND `reversals`.`created_at` BETWEEN ? AND ?
                   AND `reversals`.`merchant_id` = ?
                   AND `refunds`.`base_amount` <= ?
            LIMIT  1
         */
        $query = $this->newQuery()
            ->selectRaw('SUM(' . $this->dbColumn(Entity::TAX) . ') AS tax, SUM(' . $this->dbColumn(Entity::FEE) . ') AS fee')
            ->where($this->dbColumn(Entity::ENTITY_TYPE), '=', E::REFUND)
            ->whereBetween($this->dbColumn(Entity::CREATED_AT), [$start, $end]);

        $query->join(
            $this->repo->refund->getTableName(),
            function(JoinClause $join)
            {
                $refundIdAttr = $this->repo->refund->dbColumn(Entity::ID);
                $entityIdAttr = $this->dbColumn(Entity::ENTITY_ID);

                $join->on($entityIdAttr, $refundIdAttr);
            });

        $query->merchantId($merchantId);

        $refundBaseAmountColumn = $this->repo->refund->dbColumn(Refund\Entity::BASE_AMOUNT);

        switch ($filterType)
        {
            case InvoiceType::REFUND_LTE_1K:
                $query = $query->where($refundBaseAmountColumn, '<=', Calculator\Base::REFUND_SLAB1_TAX_CUT_OFF);

                break;

            case InvoiceType::REFUND_GT_1K_LTE_10K:
                $query = $query->where($refundBaseAmountColumn, '>', Calculator\Base::REFUND_SLAB1_TAX_CUT_OFF)
                    ->where($refundBaseAmountColumn, '<=', Calculator\Base::REFUND_SLAB2_TAX_CUT_OFF);

                break;

            case InvoiceType::REFUND_GT_10K:
                $query = $query->where($refundBaseAmountColumn, '>', Calculator\Base::REFUND_SLAB2_TAX_CUT_OFF);

                break;

            default:
                throw new Exception\LogicException('Invalid merchant invoice type: ', $filterType);
        }

        return $query->first();
    }

    public function fetchFromUtr($utr, $balanceId): Base\Collection
    {
        $reversals = $this->newQuery()
                          ->where(Entity::BALANCE_ID, $balanceId)
                          ->where(Entity::UTR, $utr)
                          ->get();

        if ($reversals->count() > 1)
        {
            throw new LogicException(
                'Found too many reversals for a given UTR',
                ErrorCode::SERVER_ERROR_MULTIPLE_REVERSALS_FOR_UTR,
                [
                    'balance_id'    => $balanceId,
                    'utr'           => $utr,
                    'count'         => $reversals->count()
                ]);
        }

        return $reversals;
    }
}
