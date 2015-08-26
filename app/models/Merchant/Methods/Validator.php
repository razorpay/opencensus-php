<?php

namespace Models\Merchant\Methods;

use Models\Base;
use Models\Payment\Processor\Netbanking;
use EE\Exception;
use EE\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $addBanksRules = array(
        'banks' => 'sometimes|array');

    protected static $addBanksValidators = array(
        'banks');

    protected static $setMethodsRules = array(
        'paytm' => 'sometimes|boolean',
        'card'  => 'sometimes|boolean',
        'banks' => 'sometimes|array');

    protected static $setMethodsValidators = array(
        'methodBanks');

    protected function validateMethodBanks(array $input)
    {
        if (isset($input['banks']) === false)
        {
            return;
        }

        $this->validateBanks($input);
    }

    protected function validateBanks(array $input)
    {
        if (is_array($input['banks']) === false)
        {
            throw new Exception\LogicException('Not an array');
        }

        $banks = $input['banks'];

        $unsupported = Netbanking::findUnsupportedBanks($banks);

        if (count($unsupported) !== 0)
        {
            $msg = implode(', ', $unsupported) . ' are either invalid or unsupported banks';

            throw new Exception\BadRequestValidationFailureException(
                $msg, 'banks');
        }

        $uniqBanks = array_unique($banks);

        if (count($banks) !== count($uniqBanks))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Some banks are repeated',
                'banks');
        }
    }
}