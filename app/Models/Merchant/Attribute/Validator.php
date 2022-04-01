<?php

namespace RZP\Models\Merchant\Attribute;

use RZP\Base;
use RZP\Exception;

/**
 *
 *
 */
class Validator extends Base\Validator{

    const PREFERENCES = 'preferences';

    protected static $createRules = [
        Entity::PRODUCT => 'required|string|in:primary,banking',
        Entity::GROUP => 'required|string',
        Entity::TYPE => 'required|string',
        Entity::VALUE => 'required|string'
    ];

    protected static $editRules = [
        Entity::PRODUCT => 'required|string|in:primary,banking',
        Entity::GROUP => 'required|string',
        Entity::TYPE => 'required|string',
        Entity::VALUE => 'string|nullable'
    ];

    protected static $upsertInputValidationRules = [
        Entity::TYPE => 'required|string',
        Entity::VALUE => 'required|string'
    ];

    protected static $adminUpsertRules = [
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
        Entity::PRODUCT     => 'sometimes|string|in:primary,banking',
        self::PREFERENCES   => 'required|array',
    ];

    public function validateGroupAndType(array $item){

        if (Entity::isValidGroupAndType($item[Entity::GROUP], $item[Entity::TYPE]) === false){
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Group: '.$item[Entity::GROUP].' AND/OR Invalid Type: '.$item[Entity::TYPE]);
        }
    }
}
