<?php

namespace RZP\Services\Mock;

use RZP\Services\SmartCollect as BaseSmartCollect;

class SmartCollect extends BaseSmartCollect
{
    public function processBankTransfer($data)
    {
        return [
            'status_code' => 200,
            'body'        => [
                'message'        => 'null',
                'transaction_id' => 'CMS480098890',
                'valid'          => true
            ]
        ];
    }

    public function processQrCodePayment($path, $data)
    {
        return [];
    }
}
