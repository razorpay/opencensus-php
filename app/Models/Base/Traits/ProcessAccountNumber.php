<?php

namespace RZP\Models\Base\Traits;

use RZP\Models\Merchant;

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
     * @throws \RZP\Exception\BadRequestException
     */
    protected function processAccountNumber(array & $input) : Merchant\Balance\Entity
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        return $merchantValidator->validateAndTranslateAccountNumberForBanking($input);
    }
}
