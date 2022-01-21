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
            Entity::DEVICE_ID                       => 'string',
            Entity::CLIENT_ID                       => 'string',
            Entity::CUSTOMER_ID                     => 'string',
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
            Entity::EXPIRE_AT                       => 'epoch|' . $expireAt,
            Entity::START_DATE                      => 'epoch',
            Entity::END_DATE                        => 'epoch',
            Entity::DETAILS                         => 'string',
            Entity::ACTIVE                          => 'boolean',
            Entity::DESCRIPTION                     => 'string',
            Entity::ACTION                          => 'string',
            Entity::NETWORK_TRANSACTION_ID          => 'string',
            Entity::GATEWAY_TRANSACTION_ID          => 'string',
            Entity::GATEWAY_REFERENCE_ID            => 'string',
            Entity::RRN                             => 'string',
            Entity::REF_ID                          => 'string|max:50',
            Entity::REF_URL                         => 'string|max:255',
            Entity::MCC                             => 'string|size:4',
            Entity::GATEWAY_ERROR_CODE              => 'string',
            Entity::GATEWAY_ERROR_DESCRIPTION       => 'string',
            Entity::RISK_SCORES                     => 'string',
            Entity::GATEWAY_DATA                    => 'string',
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
