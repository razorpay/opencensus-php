<?php


namespace RZP\Models\Mpan;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MPAN         => 'required|digits:16',
        Entity::NETWORK      => 'required|in:Visa,RuPay,MasterCard',
    ];

    protected static $editRules = [
        Entity::ASSIGNED     => 'required|boolean',
    ];

    protected static $issueMpansRules = [
        Constants::COUNT     => 'required|numeric|max:5000',
        Entity::NETWORK      => 'required|in:Visa,RuPay,MasterCard',
    ];
}
