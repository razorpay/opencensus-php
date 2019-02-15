<?php

namespace RZP\Services\Mock\FTS;

use RZP\Services\FTS\FundTransfer as BaseFundTransfer;

class FundTransfer extends BaseFundTransfer
{
    public function requestFundTransfer(string $ftaId, string $accountType):array
    {
        return [
                "message" => "fund transfer sent to fts."
            ];
    }
}