<?php

namespace RZP\Models\Gateway\Priority;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

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
        Gateway::EBS         => 'sometimes|numeric|min:0|max:100'
    ];

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
        $valid = true;

        switch ($method) {
            case Method::CARD:
                $valid = $this->validCardGateways($input);
                break;

            case Method::NETBANKING:
                $valid = $this->validNetBankingGateways($input);
                break;

            default:
                throw new Exception\LogicException("Should not come here");
                break;
        }

        if ($valid === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY_FOR_METHOD);
        }
    }

    protected function validCardGateways(array $input)
    {
        $inputGateways = array_keys($input);

        $cardGateways = Defaults::$directCardGatewaysOrder;

        // Validates that all gateways exist in card gateways list.
        return (count($inputGateways) === count(array_intersect($inputGateways, $cardGateways)));
    }

    protected function validNetBankingGateways(array $input)
    {
        $inputGateways = array_keys($input);

        $netbankingGateways = Defaults::$directNetbankingGatewaysOrder;

        return (count($inputGateways) === count(array_intersect($inputGateways, $netbankingGateways)));
    }
}
