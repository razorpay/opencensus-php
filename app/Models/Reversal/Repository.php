<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;
use RZP\Models\Transfer;

class Repository extends Base\Repository
{
    protected $entity = 'reversal';

    protected $entityFetchParamRules = [
        Entity::TRANSFER_ID         => 'sometimes|string|size:18|public_id',
    ];

    protected $appFetchParamRules = [
        Entity::TRANSACTION_ID      => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|size:14',
        Entity::TRANSFER_ID         => 'sometimes|alpha_num|min:14',
    ];

    protected function addQueryParamTransferId($query, $params)
    {
        $transferId = Transfer\Entity::verifyIdAndSilentlyStripSign($params[Entity::TRANSFER_ID]);

        $transferIdAttribute = $this->repo->reversal->dbColumn(Entity::TRANSFER_ID);

        $query->where($transferIdAttribute, '=', $transferId);
    }
}
