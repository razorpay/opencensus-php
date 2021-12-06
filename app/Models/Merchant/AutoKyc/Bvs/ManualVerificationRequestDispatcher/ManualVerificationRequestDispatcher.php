<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\ManualVerificationRequestDispatcher;

interface ManualVerificationRequestDispatcher
{
    public function triggerBVSRequest(): void;
}
