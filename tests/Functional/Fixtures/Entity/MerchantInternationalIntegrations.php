<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class MerchantInternationalIntegrations extends Base
{
    public function createNiumIntegration($attributes)
    {
        $attributes['merchant_id'] = $attributes['merchant_id'] ?? 'DefaultPartner';
        $attributes['integration_entity'] = 'nium';
        $attributes['integration_key'] = $attributes['integration_key'] ?? 'nium1234';
        $attributes['notes'] = $attributes['notes'] ?? [];

        $internationalIntegration = parent::create($attributes);

        $internationalIntegration->saveOrFail();

        return $internationalIntegration;
    }
}
