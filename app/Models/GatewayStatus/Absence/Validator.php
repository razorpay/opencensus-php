<?php

namespace RZP\Models\GatewayStatus\Absence;

use App;

use RZP\Base;
use RZP\Models\Payment\Gateway;
use RZP\Exception;
use RZP\Models\Payment\Method;
use RZP\Models\Card\Network;
use RZP\Models\Bank\IFSC;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY         => 'required|string|max:255|custom',
        Entity::REASON_CODE     => 'required|string|max:30|custom',
        Entity::FROM            => 'required|integer',
        Entity::METHOD          => 'required|string|max:30',
        Entity::SOURCE          => 'required|string|max:30|custom',
        Entity::ISSUER          => 'sometimes|string|max:50',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num|size:14',
        Entity::CARD_TYPE       => 'sometimes|string|max:10',
        Entity::NETWORK         => 'sometimes|string|max:10',
        Entity::COMMENT         => 'sometimes|string|max:500',
        Entity::TO              => 'sometimes|integer',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
    ];

    protected static $editRules = [
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
    ];

    protected static $createValidators = [
        Entity::TO, Entity::METHOD, Entity::ISSUER, Entity::CARD_TYPE, Entity::NETWORK
    ];

    protected static $editValidators = [
        Entity::TO
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

    public function validateReasonCode($attribute, $reasonCode)
    {
        if (ReasonCode::isValidReasonCode($reasonCode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Reason Code: '. $reasonCode . ' is not valid'
            );
        }
    }

    public function validateSource($attribute, $source)
    {
        if (ReasonCode::isValidSource($source) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Source : '. $source . ' is not valid'
            );
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

    public function validateIssuer($input)
    {
        $issuer = $input[Entity::ISSUER] ?? null;

        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY];

        // we need the name of the bank for netbanking and it cannot be empty
        if ($method === Method::NETBANKING)
        {
            if (empty($issuer) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Issuer: '. $issuer .' cannot be empty for method: '.$method
                );
            }

            $gateways = Gateway::getGatewaysForNetbankingBank($issuer);

            if (in_array($gateway, $gateways) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Issuer: '. $issuer .' is not supported for gateway: '.$gateway
                );
            }
        }

        if ($issuer === null)
        {
            return ;
        }

        if (($method !== Method::WALLET) and (IFSC::exists(strtoupper($issuer)) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Issuer: '. $issuer. ' is not a valid Bank Name'
            );
        }

        if (($method === Method::WALLET) and in_array($issuer, Gateway::$methodMap[Method::WALLET]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Issuer: '. $issuer. ' is not a valid Wallet'
            );
        }
    }

    public function validateCardType($input)
    {
        $cardType = $input[Entity::CARD_TYPE] ?? null;

        if (empty($cardType) === true)
        {
            return;
        }

        $method = $input[Entity::METHOD];

        // card type is not applicable for netbanking
        if ((strtolower($method) === Method::CARD) and (in_array(strtolower($cardType), ['debit', 'credit']) == false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Card Type: '.$cardType. ' is not supported'
            );
        }
    }

    public function validateNetwork($input)
    {
        $network = $input[Entity::NETWORK] ?? null;

        if (empty($network) === true)
        {
            return;
        }

        $network = strtoupper($network);

        if (Network::isValidNetwork($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Network: '. $input[Entity::NETWORK] . ' is not a valid network'
            );
        }

        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY];

        $cardNetWork = Gateway::$cardNetworkMap[$gateway];

        if ((strtolower($method) === Method::CARD) and (in_array($network, $cardNetWork) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                  'Network: '. $input[Entity::NETWORK] . ' is not a valid network for gateway: '.$gateway
            );
        }
    }

    public function validateMethod($input)
    {
        $methods = Method::getAllPaymentMethods();

        $method = strtolower($input[Entity::METHOD]);

        if (in_array($method, $methods) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Method: '. $method . ' is not valid'
            );
        }

        $gateway = $input[Entity::GATEWAY];

        if (Gateway::isMethodSupported($input[Entity::METHOD], $gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Method: '.$input['method'] .' is not supported for gateway: '. $gateway
            );
        }
    }
}
