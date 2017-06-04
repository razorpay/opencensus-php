<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment\Processor\Netbanking;

class Validator extends Base\Validator
{
    protected static $addBanksRules = array(
        Entity::BANKS => 'sometimes|array');

    protected static $addBanksValidators = array(
        Entity::BANKS);

    protected static $setMethodsRules = array(
        Entity::BANKS       => 'sometimes|array',
        Entity::NETBANKING  => 'sometimes|boolean',
        Entity::AMEX        => 'sometimes|boolean',
        Entity::PAYTM       => 'sometimes|boolean',
        Entity::PAYZAPP     => 'sometimes|boolean',
        Entity::PAYUMONEY   => 'sometimes|boolean',
        Entity::AIRTELMONEY => 'sometimes|boolean',
        Entity::OPENWALLET  => 'sometimes|boolean',
        Entity::OLAMONEY    => 'sometimes|boolean',
        Entity::MOBIKWIK    => 'sometimes|boolean',
        Entity::FREECHARGE  => 'sometimes|boolean',
        Entity::JIOMONEY    => 'sometimes|boolean',
        Entity::EMI         => 'sometimes|boolean',
        Entity::CREDIT_CARD => 'sometimes|boolean',
        Entity::DEBIT_CARD  => 'sometimes|boolean',
        Entity::UPI         => 'sometimes|boolean',
        Entity::AEPS        => 'sometimes|boolean',
        Entity::MPESA       => 'sometimes|boolean',
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
