<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Base;
use RZP\Models\Payment\Gateway;
use RZP\Exception;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Method;
use RZP\Models\Card\Network;
use RZP\Models\Bank\IFSC;
use App;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY         => 'required|string|max:255|custom',
        Entity::ISSUER          => 'sometimes|string|max:50',
        Entity::REASON_CODE     => 'required|string|max:30|custom',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num|size:14',
        Entity::CARD_TYPE       => 'sometimes|string|max:10',
        Entity::NETWORK         => 'sometimes|string|max:10',
        Entity::METHOD          => 'required|string|max:30',
        Entity::COMMENT         => 'sometimes|string|max:500',
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
    ];

    protected static $editRules = [
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
    ];

    protected static $createValidators = [
        'to', 'issuer', 'card_type', 'network', 'method',//'terminal_id'
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

    public function validateReasonCode($attribute, $reasonCode)
    {
        if (defined('ReasonCode::'.strtoupper($reasonCode)) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Reason Code: '. $reasonCode . ' is not valid'
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
        $issuer = $input[Entity::ISSUER];

        $method = $input[Entity::METHOD];

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
        $method = $input['method'];

        $cardType = $input['card_type'];

        if (empty($cardType) === true)
        {
            return;
        }

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
        $method = $input['method'];

        $network = $input['network'];

        if (empty($network) === true)
        {
            return;
        }

        if ((strtolower($method) === Method::CARD) and (isset(Network::$networks[strtoupper($network)]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                  'Network: '. $input['network'] . ' is not a valid network'
            );
        }
    }

    public function validateMethod($input)
    {
        $methods = Method::getAllPaymentMethods();

        $method = strtolower($input['method']);

        if (in_array($method, $methods) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Method: '. $method . ' is not valid'
            );
        }

        $gateway = $input['gateway'];

        if (Gateway::isMethodSupported($input['method'], $gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Method: '.$input['method'] .' is not supported for gateway: '. $gateway
            );
        }
    }

    /*public function validateTerminalId($input)
    {
        $terminalId = $input[Entity::TERMINAL_ID];

        $app = App::getFacadeRoot();

        $repo = $app['repo'];
        
        $terminal = $repo->terminal->getById($terminalId);

        if ($terminal === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Terminal Id: '.$terminal. ' is not a valid terminal id'
            );
        }
    }*/
}
