<?php

namespace Models\Merchant\Methods;

use Models\Base;
use Models\Payment\Processor\Netbanking;
use EE\Exception;
use EE\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $addBanksRules = array(
        Entity::BANKS => 'sometimes|array');

    protected static $addBanksValidators = array(
        Entity::BANKS);

    protected static $setMethodsRules = array(
        Entity::BANKS       => 'sometimes|array',
        Entity::CARD        => 'sometimes|boolean',
        Entity::AMEX        => 'sometimes|boolean',
        Entity::PAYTM       => 'sometimes|boolean',
        Entity::PAYZAPP     => 'sometimes|boolean',
        Entity::PAYUMONEY   => 'sometimes|boolean',
        Entity::MOBIKWIK    => 'sometimes|boolean',
        Entity::EMI         => 'sometimes|boolean',
    );

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