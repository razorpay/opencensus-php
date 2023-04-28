<?php

namespace RZP\Models\Merchant\Merchant1ccConfig;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function createAndSaveConfig(Merchant\Entity $merchant, $input)
    {
        $input[Entity::MERCHANT_ID] = $merchant->getId();

        $config = (new Entity)->build($input);

        $config->generateId();

        $this->repo->merchant_1cc_configs->saveOrFail($config);

        return $config;
    }

    public function get1ccConfigByMerchantIdAndType(string $merchantId,string $type)
    {
       return $this->repo->merchant_1cc_configs->findByMerchantAndConfigType(
            $merchantId,
            $type
        );
    }

    public function isShopifyShippingOverrideSet(string $merchantId): bool
    {
        $val = (new Repository())
            ->findByMerchantAndConfigType(
                $merchantId,
                Type::SHOPIFY_SHIPPING_OVERRIDE
            );
        if ($val === null)
        {
            return false;
        }
        return $val->getValue() === "1";
    }

    // This is used to differentiate between multiple shipping configurations for a single merchant
    // Currenlty in use to support Chumbak's furniture based shipping
    public function getShippingVariantStrategy(string $merchantId): string
    {
        $val = (new Repository())
            ->findByMerchantAndConfigType(
                $merchantId,
                Type::SHIPPING_VARIANT_STRATEGY
            );
        if ($val === null)
        {
            return '';
        }
        return $val->getValue() ?? '';
    }

    public function getShippingVariants(string $merchantId): array
    {
        $val = (new Repository())
            ->findByMerchantAndConfigType(
                $merchantId,
                Type::SHIPPING_VARIANTS
            );
        if ($val === null)
        {
            return [];
        }
        return $val->getValueJson() ?? [];
    }
}
