<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::PAYMENT_ID              => 'required|alpha_num|size:14',
        Entity::MERCHANT_ID             => 'required|alpha_num|size:14',
        Entity::CHECKOUT_ID             => 'sometimes|alpha_num|size:14',
        Entity::ATTEMPTS                => 'sometimes|integer|min:0',
        Entity::LIBRARY                 => 'sometimes',
        Entity::LIBRARY_VERSION         => 'sometimes',
        Entity::PLATFORM                => 'sometimes',
        Entity::PLATFORM_VERSION        => 'sometimes',
        Entity::BROWSER                 => 'sometimes',
        Entity::OS                      => 'sometimes',
        Entity::OS_VERSION              => 'sometimes',
        Entity::DEVICE                  => 'sometimes',
        Entity::REFERER                 => 'sometimes|url',
        Entity::USER_AGENT              => 'sometimes|string',
        Entity::IP                      => 'sometimes|ip',
        Entity::INTEGRATION             => 'sometimes',
        Entity::INTEGRATION_VERSION     => 'sometimes'
     );
}