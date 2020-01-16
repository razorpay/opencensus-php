<?php

namespace RZP\Models\Merchant\AutoKyc;

Interface KycEntity
{
    public function getKycId();

    public function setKycId(string $kycId);

    public function getEntityId();
}
