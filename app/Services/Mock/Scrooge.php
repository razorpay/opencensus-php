<?php

namespace RZP\Services\Mock;

use RZP\Services\Scrooge as BaseScrooge;

class Scrooge extends BaseScrooge
{
    public function initiateRefund($input)
    {
        return
            [
                "message" => "Refund process successfully initiated."
            ];
    }
}
