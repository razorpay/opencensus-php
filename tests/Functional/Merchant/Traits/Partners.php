<?php

namespace RZP\Tests\Functional\Merchant\Traits;

use RZP\Models\Merchant\Constants;

trait Partners
{
    public function getDummyPartnerAttributes(array $attributes = []): array
    {
        $defaults = [
            'id'          => '8ckeirnw84ifke',
            'merchant_id' => '10000000000000',
            'name'        => 'Internal',
            'website'     => 'https://www.razorpay.com',
            'logo_url'    => '/logo/app_logo.png',
            'category'    => null,
            'type'        => Constants::PARTNER,
        ];

        $attributes = array_merge($defaults, $attributes);

        return $attributes;
    }
}
