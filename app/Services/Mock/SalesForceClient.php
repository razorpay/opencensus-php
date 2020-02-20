<?php

namespace RZP\Services\Mock;

use RZP\Services\SalesForceClient as BaseSalesForceClient;

class SalesForceClient extends BaseSalesForceClient
{
    public function fetchAccountDetails($input)
    {
        return $input;
    }
}
