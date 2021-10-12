<?php

namespace App\Merchant;

class GenericMerchant
{
    /**
     * All of the merchant's attributes.
     *
     * @var array
     */
    protected $attributes;

    const TEST_MERCHANT_IDS = [
        '10000000000000',
        '100DemoAccount',
        Constants::X_DEMO_MERCHANT_IDS[0],
        Constants::X_DEMO_MERCHANT_IDS[1]
    ];

    /**
     * Create a new generic merchant object.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Dynamically access the merchant's attributes.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->attributes[$key];
    }

    /**
     * Dynamically set an attribute on the merchant.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Dynamically check if a value is set on the merchant.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Dynamically unset a value on the merchant.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function isTestAccount()
    {
        return in_array($this->id, self::TEST_MERCHANT_IDS, true);
    }
}
