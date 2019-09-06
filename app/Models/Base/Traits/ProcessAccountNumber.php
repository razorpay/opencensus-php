<?php


namespace RZP\Models\Base\Traits;

/**
 * Trait ProcessAccountNumber
 *
 * @package RZP\Models\Base\Traits
 *
 * Expects
 * - this->merchant
 */
trait ProcessAccountNumber
{
    /**
     * Mandate Account number and Replaces it with balance id
     *
     * @param array $input
     */
    protected function processAccountNumber(array & $input)
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateAndTranslateAccountNumberForBanking($input);
    }
}
