<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

class Pixels
{
    const PRODUCT = 'product';
    const VARIANT = 'variant';

    const GID_PRODUCT         = 'gid://shopify/Product/';
    const GID_PRODUCT_VARIANT = 'gid://shopify/ProductVariant/';

    // non critical flow so we add a catch and return an empty array
    public function getDataForFbPixels(array $checkout): array
    {
        try
        {
            if (isset($checkout['line_items']) === true)
            {
              return $this->getPixelDataFromAdminCheckout($checkout);
            }

            return $this->getPixelDataFromStorefrontCheckout($checkout);
        }
        catch (\Throwable $e)
        {
            return [];
        }

    }

    protected function getPixelDataFromAdminCheckout(array $checkout): array
    {
        $lineItems = $checkout['line_items'];

        $items = [];

        foreach ($lineItems as $lineItem)
        {
            $items[] = [
                'id'         => $lineItem['product_id'],
                'variant_id' => $lineItem['variant_id'],
                'name'       => $lineItem['title'],
                'value'      => $lineItem['price']['amount'],
                'quantity'   => $lineItem['quantity'],
            ];
        }

        return [
            'currency'     => $checkout['currency'],
            'value'        => $checkout['total_price'],
            'content_type' => self::PRODUCT,
            'contents'     => $items,
        ];
    }

    protected function getPixelDataFromStorefrontCheckout(array $checkout): array
    {
        $lineItems = $checkout['lineItems']['edges'];

        $items = [];

        foreach ($lineItems as $lineItem)
        {
            $item = $lineItem['node'];

            $variant = $item['variant'];

            $items[] = [
                'id'         => $this->getContentId($variant['product']['id'], self::PRODUCT),
                'variant_id' => $this->getContentId($variant['id'], self::VARIANT),
                'name'       => $item['title'],
                'value'      => $variant['price']['amount'],
                'quantity'   => $item['quantity'],
            ];
        }

        return [
            'currency'     => $checkout['currencyCode'],
            'value'        => $checkout['totalPrice']['amount'],
            'content_type' => self::PRODUCT,
            'contents'     => $items,
        ];
    }

    protected function getContentId(string $id, string $type): int
    {
        $base = $type === self::PRODUCT ? self::GID_PRODUCT : self::GID_PRODUCT_VARIANT;

        // To support backward compatibility of Shopify API version update from 2022-01 to 2022-10
        if(substr($id, 0, 3) != "gid")
        {
            $id = base64_decode($id);
        }

        return (int)str_replace($base, '', $id);
    }
}
