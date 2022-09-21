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

    protected static $editRules;
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

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
                  Entity::MANDATE_ID => 'required',
                  Entity::DETAILS    => 'required',
                  Entity::ACTION     => 'required',
                  Entity::STATUS     => 'required',
                  Entity::ACTIVE     => 'sometimes',
                  Entity::EXPIRE_AT  => 'sometimes',
                  Entity::REMARKS    => 'sometimes',
                ]);

        return $rules;
    }

    /**
     * @return Base\Libraries\Rules
     */
    public function makeEditRules()
    {
        $rules = $this->makeRules([
                  Entity::MANDATE_ID => 'sometimes',
                  Entity::DETAILS    => 'sometimes',
                  Entity::ACTION     => 'sometimes',
                  Entity::STATUS     => 'sometimes',
                  Entity::ACTIVE     => 'sometimes',
                  Entity::EXPIRE_AT  => 'sometimes',
                  Entity::REMARKS    => 'sometimes',
                ]);

        return $rules;
    }
}
