<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Vpa\Entity;

class VpaTransformer extends Transformer
{
    public function transform(): array
    {
        $output = [
            Entity::USERNAME                => $this->transformUsername(),
            Entity::HANDLE                  => $this->transformHandle(),
            Entity::GATEWAY_DATA            => $this->transformGatewayData(),
        ];

        return $output;
    }

    public function transformGatewayData()
    {
        $gatewayData = array_only($this->input, [
            Fields::NAME,
            Fields::BANK_CODE,
            Fields::REFERENCE_ID,
            Fields::ACCOUNT_REFERENCE_ID,
            Fields::BANK_ACCOUNT_UNIQUE_ID,
        ]);

        return $gatewayData;
    }

    public function transformUsername()
    {
        $address = $this->input[Fields::CUSTOMER_VPA];

        return explode('@', $address)[0];
    }

    public function transformHandle()
    {
        $address = $this->input[Fields::CUSTOMER_VPA];

        return explode('@', $address)[1];
    }
}
