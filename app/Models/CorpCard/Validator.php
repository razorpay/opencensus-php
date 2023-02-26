<?php

namespace RZP\Models\CorpCard;

class Validator extends \RZP\Base\Validator
{
    protected static $createRules = [
        Constants::CHANNEL        => 'required|string|in:m2p',
        Constants::ACCOUNT_NUMBER => 'required|string|size:14',
    ];

    protected static $onboardcccRules = [
        Constants::MERCHANT_ID    => 'required|string|size:14',
        Constants::ACCOUNT_NUMBER => 'required|string|size:14',
    ];
}
