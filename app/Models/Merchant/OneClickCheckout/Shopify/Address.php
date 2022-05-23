<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\OneClickCheckout;

class Address
{
    /**
     * transform Rzp to Shopify Admin API address schema
     */
    public function transformRzpToShopifyAdminAddress(array $address): array
    {
        $this->transformToShopifyAddress($address);

        $splitNames = $this->splitNameIntoFirstAndLast($address['name']);

        unset($address['name']);
        $address['first_name'] = $splitNames[0];
        $address['last_name'] = $splitNames[1];

        return $address;
    }

    /**
     * transform Rzp to Shopify Storefront API address schema
     */
    public function transformRzpToShopifyStorefrontAddress(array &$address): void
    {
        $this->transformToShopifyAddress($address);

        $splitNames = $this->splitNameIntoFirstAndLast($address['name']);

        unset($address['name']);
        $address['firstName'] = $splitNames[0];
        $address['lastLame'] = $splitNames[1];
    }

    /**
     * common transformations from Rzp -> Shopify address schema
     */
    protected function transformToShopifyAddress(array &$address): void
    {
        $address = array_merge($address,
        [
            'address1'   => $address['line1'] ?? 'address not entered',
            'address2'   => $address['line2'] ?? '',
            'country'    => $address['country'],
            'province'   => $address['state_code'] ?? $address['state'],
            'zip'        => $address['zipcode'],
            'city'       => $address['city'],
            'phone'      => $address['contact'] ?? '',
        ]);
    }

    /*
     * splits a full name into first and last name
     * shopify needs both values so last name defaulted to "."
     */
    public function splitNameIntoFirstAndLast(string $name): array
    {
        $name = preg_replace('/\s+/', ' ', trim($name));

        $words = explode(' ', $name);

        if (count($words) === 1)
        {
            $lastName = '.';

            $firstName = $name;
        }
        else
        {
            $lastName = array_pop($words);

            $firstName = implode(' ', $words);
        }

        return [$firstName, $lastName];
    }
}
