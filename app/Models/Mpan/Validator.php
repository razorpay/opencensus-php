<?php


namespace RZP\Models\Mpan;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $beforeTokenizationCreateRules = [
        Entity::MPAN         => 'required|digits:16',
        Entity::NETWORK      => 'required|in:Visa,RuPay,MasterCard',
    ];

    protected static $createRules = [
        Entity::MPAN         => 'required',
        Entity::NETWORK      => 'required|in:Visa,RuPay,MasterCard',
    ];

    protected static $editRules = [
        Entity::MPAN         => 'sometimes',
        Entity::ASSIGNED     => 'sometimes|boolean',
    ];

    protected static $issueMpansRules = [
        Constants::COUNT     => 'required|numeric|min:1|max:5000',
        Entity::NETWORK      => 'required|in:Visa,RuPay,MasterCard',
    ];

    protected static $tokenizeExistingMpansRules = [
        Constants::COUNT     => 'sometimes|numeric|min:1|max:500',  
    ];
}
