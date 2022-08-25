<?php

namespace RZP\Services\Mock;

use RZP\Services\ApachePinotClient as BaseApachePinotClient;

class ApachePinotClient extends BaseApachePinotClient
{
    public function getDataFromPinot($query, $associate = true)
    {
        return null;
    }
}
