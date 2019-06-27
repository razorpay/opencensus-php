<?php

namespace RZP\Services\Mock;

use RZP\Services\Mozart as BaseMozart;

class Mozart extends BaseMozart
{
    public function sendMozartRequest(
        string $namespace,
        string $gateway,
        string $action,
        array $input,
        $version = 'v1')
    {
        return [];
    }
}
