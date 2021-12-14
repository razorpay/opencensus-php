<?php

namespace RZP\Models\QrCodeConfig;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'qr_code_config';
    
    //select * from qr_code_config where merchant_id = $merchantId and `key` = $key and deleted_at not null limit 1
    public function findQrCodeConfigsByMerchantIdAndKey($merchantId, $key)
    {
        $result = $this->newQuery()
                       ->where(Entity::MERCHANT_ID, '=', $merchantId)
                       ->where(Entity::KEY, '=', $key)
                       ->first();

        return $result;
    }
}
