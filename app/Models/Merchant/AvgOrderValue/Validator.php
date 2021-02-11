<?php

namespace RZP\Models\Merchant\AvgOrderValue;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZp\Models\Merchant;
use RZP\Constants\Country;
use RZP\Constants\IndianStates;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Gateway\Enach\Netbanking\EnachNetbankingNpciIciciTest;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID         => 'sometimes|string|size:14',
        Entity::MIN_AOV             => 'required|numeric',
        Entity::MAX_AOV             => 'required|numeric'
    ];

    protected static $editRules = [
        Entity::MIN_AOV             => 'required|numeric',
        Entity::MAX_AOV             => 'required|numeric'
    ];
}
