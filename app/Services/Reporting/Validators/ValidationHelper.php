<?php


namespace RZP\Services\Reporting\Validators;

use RZP\Exception;

/**
 * Trait ValidationHelper Helper class for Report Request Validations
 * @package RZP\Services\Reporting
 */
trait ValidationHelper
{
    /**
     * Fails if any input email address is not present in the registered email address list.
     *
     * @param array $regEmailAddresses list of email ids registered for the merchant
     * @param array $inputEmailAddresses list of input email ids
     * @throws Exception\BadRequestValidationFailureException if any input email id is not present
     * in registered email address list
     */
    public function failIfAnyEmailIsInvalid(array $regEmailAddresses, array $inputEmailAddresses)
    {
        foreach ($inputEmailAddresses as $email)
        {
            if (in_array($email, $regEmailAddresses, true) === false)
            {
                $message = $email . ' is not a registered email address';
                throw new Exception\BadRequestValidationFailureException($message, self::EMAILS);
            }
        }
    }
}
