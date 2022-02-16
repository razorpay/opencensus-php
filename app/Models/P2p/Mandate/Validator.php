<?php

namespace RZP\Models\P2p\Mandate;

use Carbon\Carbon;
use RZP\Models\P2p\Base;

/**
 * Class Validator
 *
 * @package RZP\Models\P2p\Mandate
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
        $modes          = $this->getAllowedModes();
        $recurringTypes = $this->getAllowedRecurringTypes();
        $amountRules    = $this->getAllowedAmountRules();
        $recurringRules = $this->getAllowedRecurringRules();
        $expireAt       = $this->getExpireAtRule();

        $rules = [
            Entity::NAME                            => 'string',
            Entity::DEVICE_ID                       => 'string',
            Entity::MERCHANT_ID                     => 'string',
            Entity::CUSTOMER_ID                     => 'string',
            Entity::HANDLE                          => 'string',
            Entity::AMOUNT                          => 'integer|min:1|max:10000000',
            Entity::AMOUNT_RULE                     => 'string|' . $amountRules,
            Entity::PAYER_ID                        => 'string',
            Entity::PAYEE_ID                        => 'string',
            Entity::TYPE                            => 'string',
            Entity::FLOW                            => 'string',
            Entity::MODE                            => 'string|' . $modes,
            Entity::RECURRING_TYPE                  => 'string|' . $recurringTypes,
            Entity::RECURRING_VALUE                 => 'integer|min:1|max:31',
            Entity::RECURRING_RULE                  => 'string|' . $recurringRules,
            Entity::UMN                             => 'string',
            Entity::STATUS                          => 'string',
            Entity::INTERNAL_STATUS                 => 'string',
            Entity::START_DATE                      => 'epoch',
            Entity::END_DATE                        => 'epoch',
            Entity::DESCRIPTION                     => 'string',
            Entity::ACTION                          => 'string',
            Entity::GATEWAY                         => 'string',
            Entity::GATEWAY_DATA                    => 'string',
            Entity::INTERNAL_ERROR_CODE             => 'string',
            Entity::ERROR_CODE                      => 'string',
            Entity::ERROR_DESCRIPTION               => 'string',
            Entity::COMPLETED_AT                    => 'epoch|',
            Entity::EXPIRE_AT                       => 'epoch|' . $expireAt,
        ];

        return $rules;
    }

    /**
     * @return string
     */
    private function getAllowedModes()
    {
        return 'in:' . implode(',', Mode::$allowed);
    }

    /**
     * @return string
     */
    private function getAllowedRecurringTypes()
    {
        return 'in:' . implode(',', RecurringType::$allowed);
    }

    /**
     * @return string
     */
    private function getAllowedAmountRules()
    {
        return 'in:' . implode(',', [
                    'MAX',
                    'EXACT',
                ]);
    }

    /**
     * @return string
     */
    private function getAllowedRecurringRules()
    {
        return 'in:' . implode(',', [
                'ON',
                'BEFORE',
                'AFTER',
            ]);
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
