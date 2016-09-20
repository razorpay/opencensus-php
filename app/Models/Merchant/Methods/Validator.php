<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Base;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $addBanksRules = array(
        Entity::BANKS => 'sometimes|array');

    protected static $addBanksValidators = array(
        Entity::BANKS);

    protected static $setMethodsRules = array(
        Entity::BANKS       => 'sometimes|array',
        Entity::CARD        => 'sometimes|boolean',
        Entity::NETBANKING  => 'sometimes|boolean',
        Entity::AMEX        => 'sometimes|boolean',
        Entity::PAYTM       => 'sometimes|boolean',
        Entity::PAYZAPP     => 'sometimes|boolean',
        Entity::PAYUMONEY   => 'sometimes|boolean',
        Entity::AIRTELMONEY => 'sometimes|boolean',
        Entity::OLAMONEY    => 'sometimes|boolean',
        Entity::MOBIKWIK    => 'sometimes|boolean',
        Entity::EMI         => 'sometimes|boolean',
        Entity::CREDIT_CARD => 'sometimes|boolean',
        Entity::DEBIT_CARD  => 'sometimes|boolean',
        Entity::UPI         => 'sometimes|boolean',
    );

    protected static $setMethodsValidators = array(
        'methodBanks',
        'card');

    protected function validateMethodBanks(array $input)
    {
        if (isset($input['banks']) === false)
        {
            return;
        }

        $this->validateBanks($input);
    }

    protected function validateCard(array $input)
    {
        if ((isset($input[Entity::CREDIT_CARD])) and
            (isset($input[Entity::DEBIT_CARD])) and
            ($input[Entity::CREDIT_CARD] === '0') and
            ($input[Entity::DEBIT_CARD]) === '0')
        {
            throw new Exception\BadRequestValidationFailureException(
                'Both debit card and credit card cannot be disabled if card is enabled.');
        }
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
