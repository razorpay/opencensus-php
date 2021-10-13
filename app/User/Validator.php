<?php


namespace App\User;

use App\Base;

class Validator extends Base\Validator
{
    protected static $userFetchRules = array(
        Constants::FEATURES             => 'sometimes|string|in:0,1',
        Constants::PAYOUTS              => 'sometimes|string|in:0,1',
        Constants::TAGS                 => 'sometimes|string|in:0,1',
        Constants::SPLITZ_EXPERIMENTS   => 'sometimes|string|in:0,1',
        Constants::EXPERIMENTS          => 'sometimes|string|in:0,1',
    );

}

