<?php

namespace Functional\Partner;

use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Partner\Config as PartnerConfig;

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

    public function createConfigForPartnerApp($appId, $submerchantId = null, $attributes = [])
    {
        if ($submerchantId === null)
        {
            $attributes[PartnerConfig\Entity::ENTITY_TYPE] = PartnerConfig\Constants::APPLICATION;
            $attributes[PartnerConfig\Entity::ENTITY_ID]   = $appId;

            $attributes[PartnerConfig\Entity::ORIGIN_TYPE] = null;
            $attributes[PartnerConfig\Entity::ORIGIN_ID]   = null;
        }
        else
        {
            $attributes[PartnerConfig\Entity::ENTITY_TYPE] = PartnerConfig\Constants::MERCHANT;
            $attributes[PartnerConfig\Entity::ENTITY_ID]   = $submerchantId;

            $attributes[PartnerConfig\Entity::ORIGIN_TYPE] = PartnerConfig\Constants::APPLICATION;
            $attributes[PartnerConfig\Entity::ORIGIN_ID]   = $appId;
        }

        return $this->fixtures->create('partner_config', $attributes);
    }
}
