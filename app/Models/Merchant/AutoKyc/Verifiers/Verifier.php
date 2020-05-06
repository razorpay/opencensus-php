<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

interface Verifier
{
    public function verify(): string;

    public function getVerificationData(): array;

}
