<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Base;
use RZP\Models\Payment\Gateway;
use RZP\Exception;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Method;
use RZP\Models\Card\Network;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY         => 'required|string|max:255|custom',
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
        Entity::REASON          => 'sometimes|string|max:500',
        Entity::BANK            => 'sometimes|string|max:10',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
        Entity::CARD_TYPE       => 'sometimes|string|max:10',
        Entity::NETWORK         => 'sometimes|string|max:10',
        Entity::METHOD          => 'required|string|max:30',
    ];

    protected static $editRules = [
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
    ];

    protected static $createValidators = [
        'to', 'bank', 'card_type', 'network', 'method'
    ];

    protected static $editValidators = [
        'to'
    ];

    // Validation Notes:
    // CARD_TYPE and Network only become applicable when the the method is card. For all other
    // methods, these are not applicable. Some possible scenarios for these include:
    // Netbanking is down for a particular bank
    // debit/credit card of a particular bank is down(typically when the ACS page is down for the issuer bank)
    // visa card of a particular bank is down - not commonly observed, but still keeping it here.

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

        if (in_array($bank, $supportedBankCodes) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Bank: '. $bank. ' is not a valid Bank Name'
            );
        }

        $gatewaysForBank = Gateway::getGatewaysForNetbankingBank($bank);

        $gateway = $input[Entity::GATEWAY];

        if (in_array($gateway, $gatewaysForBank) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Bank: '. $bank. ' is not supported for Gateway: '. $gateway
            );
        }
    }

    public function validateCardType($input)
    {
        $method = $input['method'];

        // card type is not applicable for netbanking
        if (strtolower($method) === Method::CARD)
        {
            $cardType = $input['card_type'];

            if (empty($cardType) === true)
            {
                throw Exception\BadRequestValidationFailureException(
                    'Card type is empty for method: '. $method
                );
            }
        }
    }

    public function validateNetwork($input)
    {
        $method = $input['method'];

        if (strtoower($method) === Method::CARD)
        {
            $network = $input['network'];

            if (empty($network) === true)
            {
                throw Exception\BadRequestValidationFailureException(
                  'Network is empty for method: '. $method
                );
            }

            $network = strtoupper($network);

            if (isset(Network::$networks[$network]) === false)
            {
                throw Exception\BadRequestValidationFailureException(
                  'Network: '. $input['network'] . ' is not a valid network'
                );
            }
        }
    }

    public function validateMethod($input)
    {
        $methods = Method::getAllPaymentMethods();

        $method = strtolower($input['method']);

        if (isset($methods[$method]) === false)
        {
            throw Exception\BadRequestValidationFailureException(
                'Method: '. $method . ' is not valid'
            );
        }

        $gateway = $input['gateway'];

        if (Gateway::isMethodSupported($input['method'], $gateway) === false)
        {
            throw Exception\BadRequestValidationFailureException(
                'Method: '.$input['method'] .' is not supported for gateway: '. $gateway
            );
        }
    }
}
