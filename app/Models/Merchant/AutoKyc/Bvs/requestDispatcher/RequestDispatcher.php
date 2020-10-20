<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

interface RequestDispatcher
{
    public function triggerBVSRequest(): void;
}
