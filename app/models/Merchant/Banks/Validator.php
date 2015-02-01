<?php

namespace Models\Merchant\Banks;

use Models\Base;
use Models\Payment\Processor\NetBanking;
use EE\Exception;
use EE\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $addBanksRules = array(
        'banks' => 'required|array');

    protected static $addBanksValidators = array(
        'banks');

    protected function validateBanks(array $input)
    {
        $banks = $input['banks'];

        $unsupported = NetBanking::findUnsupportedBanks($banks);

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