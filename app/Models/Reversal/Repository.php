<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Reversal;
use RZP\Models\Payment\Refund;
use RZP\Exception\LogicException;

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
                    'utr'           => $utr
                ]);
        }

        return $reversals;
    }
}
