<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Base;
use RZP\Models\Payment\Gateway;
use RZP\Exception;
use RZP\Models\Payment\Processor\Netbanking;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY         => 'required|string|max:255|custom',
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
        Entity::REASON          => 'sometimes|string|max:500',
        Entity::BANK            => 'sometimes|string|max:255',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
    ];

    protected static $editRules = [
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
    ];

    protected static $createValidators = [
        'to', 'bank'
    ];

    protected static $editValidators = [
        'to', 'bank'
    ];

    public function validateGateway($attribute, $gateway)
    {
        $valid = Gateway::isValidGateway($gateway);

        if ($valid === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway [' . $gateway . '] does not exist');
        }
    }

    public function validateTo($input)
    {
        if (empty($input[Entity::TO]) === true)
        {
            return;
        }

        $to = $input[Entity::TO];

        $from = $input[Entity::FROM];

        if ($to < $from)
        {
            throw new Exception\BadRequestValidationFailureException(
                'From : ' . $from . ' less than To :' . $to
            );
        }

        if ($from > Entity::END_OF_TIME)
        {
            throw new Exception\BadRequestValidationFailureException(
                'From: '. $from. ' is greater than End of Time:' .Entity::END_OF_TIME
            );
        }

        if ($to > Entity::END_OF_TIME)
        {
            throw new Exception\BadRequestValidationFailureException(
                'To: '. $to. ' is greater than End of Time:' .Entity::END_OF_TIME
            );
        }
    }

    public function validateBank($input)
    {
        if (empty($input[Entity::BANK]) === true)
        {
            return;
        }

        $bank = $input[Entity::BANK];

        $supportedBankCodes = Netbanking::getAllBanks();

        $bankNamesMap = array_flip(Netbanking::getNames($supportedBankCodes));

        // check if the given bank name is valid
        if (array_key_exists($bank, $bankNamesMap) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Bank: '. $bank. ' is not a valid Bank Name'
            );
        }
        // TODO: check if the bank is valid for the given gateway

        $bankCode = $bankNamesMap[$bank];

        $gatewaysForBank = Gateway::getGatewaysForNetbankingBank($bankCode);

        $gateway = $input[Entity::GATEWAY];

        if (in_array($gateway, $gatewaysForBank) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Bank: '. $bank. ' is not supported for Gateway: '. $gateway
            );
        }
    }
}
