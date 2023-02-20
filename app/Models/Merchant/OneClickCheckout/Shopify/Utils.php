<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

/**
 * util functions for shopify
 */
class Utils
{

    public function getLineItemsFromCart(array $cart): array
    {
        $items = $cart['items'];
        $lineItems = array();
        if (empty($items))
        {
            // TODO: cart is empty, now what?
        }

        foreach ($items as $item)
        {
            $properties = $item['properties'] ?? [];

            $attributes = [];

            foreach ($properties as $key => $value)
            {
                $keyType   = gettype($key);
                $valueType = gettype($value);

                $attributes[] = [
                    'key'   => ($keyType != 'string') ?  json_encode($key) : $key,
                    'value' => ($valueType != 'string') ?  json_encode($value) : $value
                ];
            }

            $lineItems[] = [
              'variant_id'          => $item['variant_id'],
              'quantity'            => (int)$item['quantity'],
              'customAttributes'    => $attributes
            ];
        }

        return $lineItems;
    }

    public function convertToGraphqlId(array $items): array
    {
        $lineItems = array();

        foreach ($items as $item)
        {
            $lineItems[] = [
              'variantId' => $this->convertToFormat($item['variant_id'], 'variant'),
              'quantity'   => $item['quantity'],
              'customAttributes' => $item['customAttributes']
            ];
        }
        return ['lineItems' => $lineItems];
    }

    public function convertToFormat(string $id, string $type): string
    {
        switch ($type)
        {
            case 'product':
                $id = Constants::GID_PRODUCT . $id;
                break;

            case 'variant':
                $id = Constants::GID_PRODUCT_VARIANT . $id;
                break;

            case 'checkout':
                $id = Constants::GID_CHECKOUT . $id;
                break;
        }
        return $id;
    }

    public function formatNumber($num, $decimals = 2)
    {
        return number_format($num, $decimals, '.', '');
    }

    public function traceResponseTime(string $metric, int $startTime, array $dimensions = [])
    {
        $duration = millitime() - $startTime;

        $this->trace->histogram($metric, $duration, $dimensions);
    }

    public function stripAndReturnShopId(string $shop): string
    {
        if (strpos($shop, Constants::MY_SHOPIFY) !== false)
        {
            $shop = explode(Constants::MY_SHOPIFY, $shop)[0];
        }

        return $shop;
    }
}
