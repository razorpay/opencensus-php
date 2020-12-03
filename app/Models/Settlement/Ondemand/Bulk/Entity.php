<?php

namespace RZP\Models\Settlement\Ondemand\Bulk;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $entity = 'settlement.ondemand.bulk';

    protected $generateIdOnCreate = true;

    const ID_LENGTH = 14;

    const ID                                = 'id';
    const SETTLEMENT_ONDEMAND_TRANSFER_ID   = 'settlement_ondemand_transfer_id';
    const SETTLEMENT_ONDEMAND_ID            = 'settlement_ondemand_id';
    const AMOUNT                            = 'amount';
    const CREATED_AT                        = 'created_at';
    const UPDATED_AT                        = 'updated_at';
    const DELETED_AT                        = 'deleted_at';

    protected $fillable = [
        self::SETTLEMENT_ONDEMAND_ID,
        self::AMOUNT,
        self::SETTLEMENT_ONDEMAND_TRANSFER_ID,
        ];

    public function setOndemandTransferId($id)
    {
        $this->setAttribute(self::SETTLEMENT_ONDEMAND_TRANSFER_ID, $id);
    }

}
