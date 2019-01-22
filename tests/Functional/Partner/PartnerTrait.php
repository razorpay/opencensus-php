<?php

namespace Functional\Partner;

use RZP\Tests\Functional\OAuth\OAuthTrait;

trait PartnerTrait
{
    use OAuthTrait;

    public function setUpPartnerMerchantAppAndGetClient(
        string $env = 'dev',
        array $attributes = [],
        string $partnerId = '10000000000000')
    {
        $attributes = array_merge($attributes, ['merchant_id' => $partnerId]);

        $client = $this->createPartnerApplicationAndGetClientByEnv($env, $attributes);

        $this->fixtures->edit('merchant', $partnerId, ['partner_type' => 'fully_managed']);

        $this->fixtures->merchant->addFeatures(['partner']);

        return $client;
    }
}
