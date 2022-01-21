<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider;

use RZP\Base;

class Validator extends Base\Validator
{

    protected static $shippingProviderRules = [
        Constants::SHIPPING_PROVIDER_ID     => 'required|string|size:14',
        Constants::ENABLE_COD               => 'sometimes|boolean',
        Constants::COD_FEE_RULE             => 'required_if:enable_cod,true',
        Constants::SHIPPING_FEE_RULE        => 'required',
        Constants::WAREHOUSE_PINCODE        => 'required|string|size:6',
    ];

    protected static $feeRuleRules = [
        Constants::FEE_RULE_TYPE            =>
            'required|string|in:'. Constants::FLAT_FEE_RULE_TYPE . ',' . Constants::FREE_FEE_RULE_TYPE . ',' . Constants::SLABS_FEE_RULE_TYPE,
        Constants::SLABS                    => 'required_if:rule_type,slabs|array|min:1|max:10',
        Constants::FLAT_FEE                 => 'required_if:rule_type,flat|integer|min:0|max:1000000000000',
    ];

    protected static $slabRules = [
        Constants::FEE => 'required|integer|min:0|max:1000000000000',
        Constants::LTE => 'required|integer|min:0|max:1000000000000',
        Constants::GTE => 'required|integer|min:0|max:1000000000000',
    ];

    protected static $listRules = [
        Constants::SHIPPING_PROVIDER_ID  => 'required|string|size:14'
    ];

}
