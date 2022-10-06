<?php

namespace RZP\Models\P2p\Mandate;

use Carbon\Carbon;
use RZP\Models\P2p\Base;
use phpDocumentor\Reflection\Types\Parent_;

/**
 * Class Validator
 *
 * @package RZP\Models\P2p\Mandate
 */
class Validator extends Base\Validator
{

    protected static $fetchAllRules;
    protected static $fetchRules;

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
        $endDate        = $this->getEndDateRule();
        $pauseStartRule = $this->getPauseStartRule();
        $pauseEndRule   = $this->getPauseEndRule();

        $rules = [
            Entity::NAME                            => 'string',
            Entity::DEVICE_ID                       => 'string',
            Entity::MERCHANT_ID                     => 'string',
            Entity::CUSTOMER_ID                     => 'string',
            Entity::HANDLE                          => 'string',
            Entity::AMOUNT                          => 'integer|min:1|max:10000000',
            Entity::AMOUNT_RULE                     => 'string|' . $amountRules,
            Entity::CURRENCY                        => 'string|in:INR',
            Entity::PAYER_ID                        => 'string',
            Entity::PAYEE_ID                        => 'string',
            Entity::BANK_ACCOUNT_ID                 => 'string',
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
            Entity::END_DATE                        => 'epoch|' . $endDate,
            Entity::PAUSE_START                     => 'epoch|' . $pauseStartRule,
            Entity::PAUSE_END                       => 'epoch|' . $pauseEndRule,
            Entity::DESCRIPTION                     => 'string',
            Entity::ACTION                          => 'string',
            Entity::GATEWAY                         => 'string',
            Entity::GATEWAY_DATA                    => 'string',
            Entity::INTERNAL_ERROR_CODE             => 'string',
            Entity::ERROR_CODE                      => 'string',
            Entity::ERROR_DESCRIPTION               => 'string',
            Entity::COMPLETED_AT                    => 'epoch|',
            Entity::EXPIRE_AT                       => 'epoch|' . $expireAt,
            Entity::REVOKED_AT                      => 'epoch',
            Entity::CYCLES_COMPLETED                => 'integer',
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

    /**
     * @return string
     */
    private function getEndDateRule()
    {
        $validityEndRule = 'min:' . Carbon::today()->getTimestamp();

        return $validityEndRule;
    }

    /**
     * @return string
     */
    private function getPauseStartRule()
    {
        $pauseStartRule = 'min:' . Carbon::today()->getTimestamp();

        return $pauseStartRule;
    }

    /**
     * @return string
     */
    private function getPauseEndRule()
    {
        $pauseEndRule = 'min:' . Carbon::today()->getTimestamp() ;

        return $pauseEndRule;
    }

    public function makeCreateRules()
    {
        return $this->makeRules([
            Entity::NAME                         => 'sometimes',
            Entity::TYPE                         => 'required',
            Entity::FLOW                         => 'required',
            Entity::MODE                         => 'required',
            Entity::PAYER                        => 'sometimes',
            Entity::PAYEE                        => 'sometimes',
            Entity::PAYER_ID                     => 'sometimes',
            Entity::PAYEE_ID                     => 'sometimes',
            Entity::BANK_ACCOUNT_ID              => 'sometimes',
            Entity::AMOUNT                       => 'required',
            Entity::AMOUNT_RULE                  => 'required',
            Entity::CURRENCY                     => 'required',
            Entity::RECURRING_TYPE               => 'required',
            Entity::RECURRING_RULE               => 'sometimes',
            Entity::RECURRING_VALUE              => 'sometimes',
            Entity::DESCRIPTION                  => 'sometimes',
            Entity::GATEWAY                      => 'sometimes',
            Entity::STATUS                       => 'required',
            Entity::INTERNAL_STATUS              => 'required',
            Entity::UMN                          => 'sometimes',
            Entity::EXPIRE_AT                    => 'sometimes',
            Entity::START_DATE                   => 'sometimes',
            Entity::END_DATE                     => 'sometimes',
            Entity::GATEWAY_DATA                 => 'sometimes',
            Entity::ACTION                       => 'sometimes',
            Entity::CYCLES_COMPLETED             => 'sometimes',
            Entity::HANDLE                       => 'sometimes',
        ]);
    }

    public function makeFetchAllRules()
    {
        $parentRule = parent::makeFetchAllRules();

        $rules = $this->makeRules([
                  Entity::STATUS   => 'sometimes',
                  Entity::RESPONSE => 'sometimes',
                ]);

        return $this->makeRules(array_merge($rules, $parentRule));
    }

    public function makeFetchRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }
}
