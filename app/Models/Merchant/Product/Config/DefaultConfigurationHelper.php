<?php

namespace RZP\Models\Merchant\Product\Config;

use App;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Product\Name;

class DefaultConfigurationHelper
{

    /**
     * This function would return the input as is if input is not empty. If input is empty, fetches the default
     * configuration for the product specified
     *
     * @param array  $input
     * @param string $productName
     *
     * @return array
     */
    public function getDefaultConfiguration(string $productName, array $input, Merchant\Entity $partner): array
    {
        if (empty($input) === true)
        {
            $input = $this->fetchDefaultConfiguration($productName);
            $input = $this->ignoreDefaultConfiguration($input, $partner);
        }

        return $input;
    }

    private function fetchDefaultConfiguration(string $productName): array
    {
        $data = [];

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
            case Name::PAYMENT_LINKS:
                $data = Defaults::PAYMENT_GATEWAY;
                break;
            case Name::ROUTE:
                $data = Defaults::ROUTE;
                break;
        }

        return $data;
    }

    /**
     * This function iterate through mapping configuration fields and verify if value is already set for partner in DB,
     * then ignore default configuration.
     *
     * @param array           $input
     * @param Merchant\Entity $partner
     *
     * @return array
     */
    private function ignoreDefaultConfiguration(array $input, Merchant\Entity $partner): array
    {
        foreach (Defaults:: PRODUCT_CONFIG_MERCHANT_FIELD_MAPPING as $field => $actualField)
        {
            $path = $this->searchKeyRecursively($field, $input, '');

            if ($path != false && $partner->isFieldHasValue($actualField))
            {
                $this->unsetKey($path, $input);
            }
        }

        return $input;
    }

    /**
     * Logic to Search for any key in nested associative array and returns key string.
     *
     * @param $key
     * @param $value
     * @param $currKey
     *
     * @return false|string
     */
    private function searchKeyRecursively($key, $value, $currKey)
    {

        foreach ($value as $k => $v)
        {
            if (is_array($v))
            {
                $nextKey = $this->searchKeyRecursively($key, $v, $currKey . '.' . $k);

                if ($nextKey)
                {
                    return $nextKey;
                }
            }
            else
            {
                if ($k == $key)
                {
                    return $currKey . '.' . $key;
                }
            }
        }

        return false;
    }

    /**
     * Unset  key in nested associative array
     *
     * @param $path
     * @param $input
     */
    private function unsetKey($path, &$input)
    {
        $path = preg_replace('/^./', '', $path);
        $path = explode('.', $path);
        $temp =& $input;

        foreach ($path as $key)
        {
            if (!is_array($temp[$key]))
            {
                unset($temp[$key]);
            }
            else
            {
                $temp =& $temp[$key];
            }
        }
    }
}
