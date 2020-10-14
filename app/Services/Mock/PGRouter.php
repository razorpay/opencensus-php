<?php


namespace RZP\Services\Mock;

use RZP\Services\PGRouter as BasePGRouter;


class PGRouter extends BasePGRouter
{
    public function syncOrderToPgRouter(array $input, bool $throwExceptionOnFailure = false): array
    {
        return
            [
                "message" => "Order Sync process successfully initiated."
            ];
    }
}
