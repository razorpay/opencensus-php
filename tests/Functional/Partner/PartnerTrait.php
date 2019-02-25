<?php

namespace Functional\Partner;

use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Tests\Functional\Fixtures\Entity\Org as Org;

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

    public function createPartnerAndApplication($partnerAttributes = [], $appAttributes = [])
    {
        $merchantId = $partnerAttributes['id'] ?? 'DefaultPartner';
        unset($partnerAttributes['id']);

        $defaultPartnerAttributes = ['partner_type' => 'aggregator'];
        $partnerAttributes        = array_merge($defaultPartnerAttributes, $partnerAttributes);

        $partner = $this->fixtures->merchant->createMerchantWithDetails(Org::RZP_ORG, $merchantId, $partnerAttributes);

        $defaultAppAttributes = [
            'merchant_id' => $partner->getId(),
        ];
        $appAttributes  = array_merge($defaultAppAttributes, $appAttributes);

        $app = $this->fixtures->merchant->createDummyPartnerApp($appAttributes);

        return [$partner, $app];
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

        $defaultAttributes = $this->getDefaultPartnerConfigAttributes();

        $attributes        = array_merge($defaultAttributes, $attributes);

        return $this->fixtures->create('partner_config', $attributes);
    }

    protected function getDefaultPartnerConfigAttributes()
    {
        return ['commissions_enabled' => 1];
    }

    public function createSubMerchant($merchant, $app, $subMerchantAttributes = [])
    {
        $subMerchantId = $subMerchantAttributes['id'] ?? 'submerchantNum';
        unset($subMerchantAttributes['id']);

        $this->fixtures->merchant->createAccount($subMerchantId);

        $subMerchant = $this->fixtures->merchant->edit($subMerchantId, $subMerchantAttributes);

        $accessMapData = [
            'entity_type'     => 'application',
            'entity_id'       => $app->getId(),
            'merchant_id'     => $subMerchant->getId(),
            'entity_owner_id' => $merchant->getId(),
        ];

        $accessMap = $this->fixtures->create('merchant_access_map', $accessMapData);

        return [$subMerchant, $accessMap];
    }
}
