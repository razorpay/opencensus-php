<?php

namespace RZP\Models\P2p\Client;

class GatewayData extends ArrayAttribute
{
    public function isValid(string $key): bool
    {
        // Every key is allowed
        return true;
    }
}
