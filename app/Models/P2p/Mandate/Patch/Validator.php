<?php

namespace RZP\Models\P2p\Mandate\Patch;

use Carbon\Carbon;
use RZP\Models\P2p\Base;

/**
 * Class Validator
 *
 * @package RZP\Models\P2p\Mandate\Patch
 */
class Validator extends Base\Validator
{
    /**
     * Common rules for Mandate entity attributes
     *
     * @return array|string[]
     */
    public function rules()
    {
        $expireAt  = $this->getExpireAtRule();

        $rules = [
            Entity::MANDATE_ID                      => 'string',
            Entity::DETAILS                         => 'array',
            Entity::ACTION                          => 'string',
            Entity::STATUS                          => 'string',
            Entity::ACTIVE                          => 'boolean',
            Entity::EXPIRE_AT                       => 'epoch|' . $expireAt,
            Entity::REMARKS                         => 'string'
        ];

        return $rules;
    }

    /**
     * @return string
     */
    private function getExpireAtRule()
    {
        $expireAtRule = 'min:' . Carbon::now()->addMinute()->getTimestamp() .
            'max:' . Carbon::now()->addDays(45)->getTimestamp();

        return $expireAtRule;
    }
}
