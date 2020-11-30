<?php


namespace RZP\Models\Reward\MerchantReward;

use RZP\Base;


class Validator extends Base\Validator
{
    protected static $activateDeactivateRules = [
        Entity::REWARD_ID     => 'required|string|unsigned_id',
        'activate'            => 'required|boolean',
    ];

}
