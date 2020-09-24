<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

interface StatusUpdater
{
    public function updateValidationStatus() : void ;

    public function updateMerchantContext() : void ;

    public function getUpdatedActivationStatus() : string ;
}
