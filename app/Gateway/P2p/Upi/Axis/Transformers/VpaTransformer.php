<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Vpa\Entity;
use RZP\Models\P2p\Beneficiary\Entity as Beneficiary;

class VpaTransformer extends Transformer
{
    protected $vpaKey = Fields::CUSTOMER_VPA;

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
            Fields::CUSTOMER_NAME,
            Fields::CUSTOMER_VPA,
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

    public function transformBeneficiaryName()
    {
        return $this->input[Fields::CUSTOMER_NAME];
    }

    public function transformBeneficiary()
    {
        $output = [
            Entity::USERNAME                => $this->transformUsername(),
            Entity::HANDLE                  => $this->transformHandle(),
            Beneficiary::TYPE               => Entity::VPA,
            Beneficiary::BLOCKED            => $this->input[Beneficiary::BLOCKED],
            Beneficiary::SPAMMED            => $this->input[Beneficiary::SPAMMED],
            Beneficiary::BLOCKED_AT         => null,
        ];

        if(isset($this->input[Beneficiary::NAME]) === true)
        {
            $output[Entity::BENEFICIARY_NAME] = $this->input[Beneficiary::NAME];
        }

        if ($this->input[Beneficiary::BLOCKED] === true)
        {
            $output[Beneficiary::BLOCKED_AT] = $this->toTimestamp($this->input[Fields::BLOCKED_AT] ?? null);
        }

        return $output;
    }
}
