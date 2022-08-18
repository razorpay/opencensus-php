<?php

namespace RZP\Services\Mock;

use RZP\Services\SmartCollect as BaseSmartCollect;

class SmartCollect extends BaseSmartCollect
{
    public function processBankTransfer($data)
    {
        return [
            'Status' => 'Success',
        ];
    }
}
