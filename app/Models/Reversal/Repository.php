<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;

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
        return $this->newQuery()
                    ->join('refunds', 'refunds.reversal_id', '=', 'reversals.id')
                    ->select('reversals.*', 'refunds.notes')
                    ->where('reversals.entity_id', $transferId)
                    ->where('reversals.entity_type', 'transfer')
                    ->where('refunds.merchant_id', $merchantId)
                    ->get();
    }
}
