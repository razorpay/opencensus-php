<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Card\Network;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY         => 'required|string|max:255|custom',
        Entity::REASON_CODE     => 'required|string|max:30|custom',
        Entity::BEGIN           => 'required|integer',
        Entity::END             => 'sometimes|integer',
        Entity::METHOD          => 'required|string|max:30',
        Entity::SOURCE          => 'required|string|max:30|custom',
        Entity::ISSUER          => 'sometimes|string|max:50',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num|size:14',
        Entity::CARD_TYPE       => 'sometimes|string|max:10',
        Entity::NETWORK         => 'sometimes|string|max:10',
        Entity::COMMENT         => 'sometimes|string|max:500',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
        Entity::PUBLIC          => 'sometimes|bool',
    ];

    protected static $editRules = [
        Entity::REASON_CODE     => 'sometimes|string|max:30|custom',
        Entity::BEGIN           => 'sometimes|integer',
        Entity::END             => 'sometimes|integer',
        Entity::SOURCE          => 'required|string|max:30|custom',
        Entity::ISSUER          => 'sometimes|string|max:50',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num|size:14',
        Entity::CARD_TYPE       => 'sometimes|string|max:10',
        Entity::NETWORK         => 'sometimes|string|max:10',
        Entity::COMMENT         => 'sometimes|string|max:500',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
        Entity::PUBLIC          => 'sometimes|bool',
    ];

    protected static $editDuplicateRules = [
        Entity::GATEWAY         => 'sometimes|string|max:255',
        Entity::METHOD          => 'sometimes|string|max:30',
        Entity::REASON_CODE     => 'sometimes|string|max:30|custom',
        Entity::BEGIN           => 'sometimes|integer',
        Entity::END             => 'sometimes|integer',
        Entity::SOURCE          => 'required|string|max:30|custom',
        Entity::ISSUER          => 'sometimes|string|max:50',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num|size:14',
        Entity::CARD_TYPE       => 'sometimes|string|max:10',
        Entity::NETWORK         => 'sometimes|string|max:10',
        Entity::COMMENT         => 'sometimes|string|max:500',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
        Entity::PUBLIC          => 'sometimes|bool',
    ];

    protected static $createValidators = [
        Entity::END,
        Entity::METHOD,
        Entity::ISSUER,
        Entity::CARD_TYPE,
        Entity::NETWORK
    ];

    protected static $editValidators = [
        Entity::END,
        Entity::ISSUER,
        Entity::CARD_TYPE,
        Entity::NETWORK
    ];

    // Validation Notes:
    // Gateway going down happens in a few cases. For instance, if HDFC netbanking is down,
    // this is applicable for all gateways
    // The following table summarizes all the use cases:
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Method     | Gateway   | Issuer   | Card Type | Network | Notes                             |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Netbanking | ALL       | HDFC     | NA        | NA      | HDFC Netbanking is down           |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Netbanking | Billdesk  | ALL      | NA        | NA      | Billdesk is down                  |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Netbanking | billdesk  | Yes Bank | NA        | NA      | Billdesk Yes bank is down         |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Card       | ALL       | SBI      | DEBIT     | ALL     | All SBI Debit Cards are down      |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Card       | ALL       | SBI      | ALL       | ALL     | All SBI Cards are down            |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Card       | ALL       | SBI      | ALL       | Visa    | ALL SBI Visa Cards are down       |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Card       | ALL       | SBI      | Debit     | Visa    | ALL SBI Visa Debit Cards are down |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Card       | Axis Migs | ALL      | ALL       | ALL     | Axis Migs is down                 |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Wallet     | Ola Money | Ola Money| NA        | NA      | Ola Money is down                 |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+
    //| Wallet     | Ola Money | Ola Money| NA        | NA      | Ola Money is down                 |
    //+------------+-----------+----------+-----------+---------+-----------------------------------+


    public function validateGateway(string $attribute, string $gateway)
    {
        if ($gateway === Entity::ALL)
        {
            return;
        }

        Gateway::validateGateway($gateway);
    }

    public function validateReasonCode(string $attribute, string $reasonCode)
    {
        if (ReasonCode::isValidReasonCode($reasonCode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Reason Code: '. $reasonCode . ' is not valid'
            );
        }
    }

    public function validateSource(string $attribute, string $source)
    {
        if (Source::isValidSource($source) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Source : '. $source . ' is not valid'
            );
        }
    }

    public function validateEnd(array $input)
    {
        if (empty($input[Entity::END]) === true)
        {
            return;
        }

        $end = $input[Entity::END];

        $begin = $input[Entity::BEGIN] ?? $this->entity->getBegin();

        if ($end < $begin)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Begin : ' . $begin . ' less than end :' . $end);
        }

        if ($begin > Entity::END_OF_TIME)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Begin: '. $begin. ' is greater than End of Time:' . Entity::END_OF_TIME);
        }

        if ($end > Entity::END_OF_TIME)
        {
            throw new Exception\BadRequestValidationFailureException(
                'End: '. $end . ' is greater than End of Time:' . Entity::END_OF_TIME);
        }
    }

    public function validateIssuer(array $input)
    {
        $issuer = $input[Entity::ISSUER] ?? $this->entity->getIssuer();

        $method = $input[Entity::METHOD] ?? $this->entity->getMethod();

        $gateway = $input[Entity::GATEWAY] ?? $this->entity->getGateway();

        $this->validateNetbankingIssuer($gateway, $method, $issuer);

        $this->validateWalletIssuer($method, $issuer);

    }

    protected function validateNetbankingIssuer(string $gateway, string $method, string $issuer = null)
    {
        // we need the name of the bank for netbanking and it cannot be empty
        if ($method === Method::NETBANKING)
        {
            if (empty($issuer) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Issuer cannot be empty for method ' . $method);
            }

            $gateways = Gateway::getGatewaysForNetbankingBank($issuer);

            if (in_array($gateway, $gateways, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Issuer '. $issuer .' is not supported for gateway: ' . $gateway);
            }

            if (in_array($issuer, [Entity::ALL, Entity::UNKNOWN, Entity::NA], true) === true)
            {
                return;
            }

            if (IFSC::exists(strtoupper($issuer)) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Issuer: '. $issuer. ' is not a valid Bank Name');
            }
        }
    }

    protected function validateWalletIssuer(string $method, string $issuer = null)
    {
        if (($method === Method::WALLET) and
            (Wallet::exists($issuer) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a valid Wallet');
        }
    }

    public function validateCardType(array $input)
    {
        $cardType = $input[Entity::CARD_TYPE] ?? $this->entity->getCardType();

        $method = $input[Entity::METHOD] ?? $this->entity->getMethod();

        if (in_array($cardType, [Entity::ALL, Entity::UNKNOWN, Entity::NA], true) === true)
        {
            return;
        }

        // card type is not applicable for netbanking
        if ((strtolower($method) === Method::CARD) and
            (Card\Type::isValidType($cardType) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Card Type: ' . $cardType . ' is not supported');
        }
    }

    public function validateNetwork(array $input)
    {
        $network = $input[Entity::NETWORK] ?? $this->entity->getNetwork();

        if (in_array($network, [Entity::ALL, Entity::UNKNOWN, Entity::NA], true) === true)
        {
            return;
        }

        $network = strtoupper($network);

        if (Network::isValidNetwork($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Network: '. $network . ' is not a valid network');
        }

        $method = $input[Entity::METHOD] ?? $this->entity->getMethod();

        $gateway = $input[Entity::GATEWAY] ?? $this->entity->getGateway();

        $cardNetWork = Gateway::$cardNetworkMap[$gateway];

        if ((strtolower($method) === Method::CARD) and
            (in_array($network, $cardNetWork, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Network: '. $input[Entity::NETWORK] . ' is not a valid network for gateway: ' . $gateway);
        }
    }

    public function validateMethod(array $input)
    {
        $method = $input[Entity::METHOD];

        Method::validateMethod($method);

        $gateway = strtolower($input[Entity::GATEWAY]);

        if (Gateway::isMethodSupported($method, $gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway ' . $gateway . ' does not support ' . $method . ' method');
        }
    }
}
