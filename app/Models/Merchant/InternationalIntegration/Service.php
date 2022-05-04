<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function createMerchantInternationalIntegration($input)
    {
        return (new Core())->createMerchantInternationalIntegration($input);
    }

    public function getMerchantInternationalIntegrations(string $mid, array $input){

        $merchant = $this->repo->merchant->findOrFailPublic($mid);
        $integrations = $this->repo->merchant_international_integrations
            ->getByMerchantId($mid);

        return $integrations->toArrayAdmin();
    }

    public function deleteMerchantInternationalIntegrations($input)
    {
        return (new Core())->deleteMerchantInternationalIntegration($input);
    }
}
