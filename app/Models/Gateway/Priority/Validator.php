<?php

namespace RZP\Models\Gateway\Priority;

use RZP\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;

class Validator extends Base\Validator
{
    protected static $validPaymentMethods = [Method::CARD, Method::NETBANKING];

    protected static $addPriorityRules = [
        Gateway::HDFC        => 'sometimes|numeric|min:0|max:100',
        Gateway::AXIS_MIGS   => 'sometimes|numeric|min:0|max:100',
        Gateway::AMEX        => 'sometimes|numeric|min:0|max:100',
        Gateway::CYBERSOURCE => 'sometimes|numeric|min:0|max:100',
        Gateway::FIRST_DATA  => 'sometimes|numeric|min:0|max:100',
        Gateway::BILLDESK    => 'sometimes|numeric|min:0|max:100',
        Gateway::EBS         => 'sometimes|numeric|min:0|max:100',
        Gateway::HITACHI     => 'sometimes|numeric|min:0|max:100',
    ];

    public function validateAddPriority(string $method, array $priorityData)
    {
        $this->validateMethod($method);

        $this->validateGatewaysForMethod($method, $priorityData);

        $this->validateInput('add_priority', $priorityData);

    }

    public function validateRemovePriority(string $method, array $data)
    {
        $validatonInput = array_flip($data);

        $this->validateGatewaysForMethod($method, $validatonInput);
    }

    public function validateMethod(string $method)
    {
        if (in_array($method, static::$validPaymentMethods, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD);
        }
    }

    public function validateGatewaysForMethod(string $method, array $input)
    {
        $inputGateways = array_keys($input);

        $validGatewaysForMethod = Defaults::GATEWAY_ORDER[$method][Mode::LIVE];

        if (empty(array_diff($inputGateways, $validGatewaysForMethod)) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY_FOR_METHOD);
        }
    }

    protected function validCardGateways(array $input)
    {
        $inputGateways = array_keys($input);

        $cardGateways = Defaults::GATEWAY_ORDER[Method::CARD][Mode::LIVE];

        // Validates that all gateways exist in card gateways list.
        return (empty(array_diff($inputGateways, $cardGateways)) === true);
    }

    protected function validNetBankingGateways(array $input)
    {
        $inputGateways = array_keys($input);

        $netbankingGateways = Defaults::GATEWAY_ORDER[Method::NETBANKING][Mode::LIVE];

        // Validates that all gateways exist in netbanking gateways list.
        return (empty(array_diff($inputGateways, $netbankingGateways)) === true);
    }
}
