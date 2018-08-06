<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;
use RZP\Models\Reversal;
use RZP\Models\Payment\Refund;

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
     * fetches reverals for a LA transfer by joining refunds
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
}
