<?php

namespace RZP\Models\Merchant\Credits\Balance;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PRODUCT     => 'required|string|in:banking',
        Entity::TYPE        => 'required|string|custom',
        Entity::EXPIRED_AT  => 'sometimes|nullable|string',
        Entity::BALANCE     => 'sometimes|integer|min:0',
    ];

    public function validateType(string $attribute, $value)
    {
        Type::exists($value);
    }

    public function validateProduct(string $product)
    {
        Product::exists($product);
    }
}
