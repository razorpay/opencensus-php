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
    /*
     * Replaces account number with balance id
     */
    protected function processAccountNumber(array & $input)
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateAndTranslateAccountNumberForBanking($input);
    }
}
