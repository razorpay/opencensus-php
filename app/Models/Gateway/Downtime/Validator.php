<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Netbanking;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY         => 'required|string|max:255|custom',
        Entity::REASON_CODE     => 'required|string|max:30|custom',
        Entity::BEGIN           => 'required|epoch',
        Entity::END             => 'sometimes|epoch',
        Entity::METHOD          => 'required|string|max:30',
        Entity::SOURCE          => 'required|string|max:30|custom',
        Entity::ISSUER          => 'sometimes|string|max:50',
        Entity::ACQUIRER        => 'sometimes|string|max:30',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num|size:14',
        Entity::CARD_TYPE       => 'sometimes|string|max:10',
        Entity::NETWORK         => 'sometimes|string|max:10',
        Entity::COMMENT         => 'sometimes|string|max:500',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
        Entity::PSP             => 'sometimes|max:255',
        Entity::VPA_HANDLE      => 'sometimes|max:255',
        Entity::MERCHANT_ID     => 'sometimes|max:255',
    ];

    protected static $editRules = [
        Entity::REASON_CODE     => 'sometimes|string|max:30|custom',
        Entity::BEGIN           => 'sometimes|epoch',
        Entity::END             => 'sometimes|epoch',
        Entity::ISSUER          => 'sometimes|string|max:50',
        Entity::ACQUIRER        => 'sometimes|string|max:30',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num|size:14',
        Entity::CARD_TYPE       => 'sometimes|string|max:10',
        Entity::NETWORK         => 'sometimes|string|max:10',
        Entity::COMMENT         => 'sometimes|string|max:500',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
        Entity::VPA_HANDLE      => 'sometimes|max:255',
        Entity::MERCHANT_ID     => 'sometimes|max:255',
    ];

    protected static $editDuplicateRules = [
        Entity::GATEWAY         => 'sometimes|string|max:255',
        Entity::METHOD          => 'sometimes|string|max:30',
        Entity::REASON_CODE     => 'sometimes|string|max:30|custom',
        Entity::SOURCE          => 'sometimes|custom',
        Entity::BEGIN           => 'sometimes|epoch',
        Entity::END             => 'sometimes|epoch',
        Entity::ISSUER          => 'sometimes|string|max:50',
        Entity::ACQUIRER        => 'sometimes|string|max:30',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num|size:14',
        Entity::CARD_TYPE       => 'sometimes|string|max:10',
        Entity::NETWORK         => 'sometimes|string|max:10',
        Entity::COMMENT         => 'sometimes|string|max:500',
        Entity::SCHEDULED       => 'sometimes|bool',
        Entity::PARTIAL         => 'sometimes|bool',
        Entity::PSP             => 'sometimes|max:255',
        Entity::VPA_HANDLE      => 'sometimes|max:255',
        Entity::MERCHANT_ID     => 'sometimes|max:255',
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
        Entity::NETWORK,
    ];

    protected static $editDuplicateValidators = [
        Entity::END,
        Entity::ISSUER,
        Entity::CARD_TYPE,
        Entity::NETWORK,
    ];

    // Validation Notes:
    // Gateway going down happens in a few cases. For instance, if HDFC netbanking is down,
    // this is applicable for all gateways
    // The following table summarizes all the use cases:
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Method     | Gateway   | Issuer   | Card Type | Network | Notes                             |
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Netbanking | ALL       | HDFC     | NA        | NA      | HDFC Netbanking is down - tested  |
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Netbanking | Billdesk  | ALL      | NA        | NA      | Billdesk is down - tested         |
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Netbanking | billdesk  | Yes Bank | NA        | NA      | Billdesk Yes bank is down - tested|
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Card       | ALL       | SBI      | DEBIT     | ALL     | All SBI Debit Cards down - tested |
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Card       | ALL       | SBI      | ALL       | ALL     | All SBI Cards are down - tested   |
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Card       | ALL       | SBI      | ALL       | Visa    | ALL SBI Visa Cards down - tested  |
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Card       | ALL       | SBI      | Debit     | Visa    | SBI Visa Debit Cards down - tested|
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Card       | Axis Migs | ALL      | ALL       | ALL     | Axis Migs is down - tested        |
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | Wallet     | Ola Money | Ola Money| NA        | NA      | Ola Money is down - tested        |
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // | UPI        | ICICI     | HDFC     | NA        | NA      | ICICI UPI gateway is down         |
    // +------------+-----------+----------+-----------+---------+-----------------------------------+
    // Note: The concept of Issuer for UPI exists via the VPA handle.

    public function validateGateway(string $attribute, string $gateway)
    {
        if ($gateway === Entity::ALL)
        {
            return;
        }

        if (Gateway::isValidGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway is invalid',
                'gateway',
                [
                    'gateway' => $gateway
                ]);
        }
    }

    public function validateReasonCode(string $attribute, string $reasonCode)
    {
        if (ReasonCode::isValidReasonCode($reasonCode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $reasonCode . ' is not a valid reason code');
        }
    }

    public function validateSource(string $attribute, string $source)
    {
        if (Source::isValid($source) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $source . ' is not a valid source');
        }

        if (($this->entity->getSource() !== null) and
            ($source !== $this->entity->getSource()))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid source given');
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

        if ($end <= $begin)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Begin : ' . $begin . ' greater than end :' . $end);
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

        switch($method)
        {
            case Method::NETBANKING:
            case Method::EMANDATE:

                $this->validateNetbankingIssuer($gateway, $issuer);

                break;

            case Method::CARD:

                $this->validateCardIssuer($issuer);

                break;

            case Method::WALLET:

                $this->validateWalletIssuer($issuer);

                break;

            case Method::UPI:

                $this->validateUpiIssuer($issuer);

                break;

            case Method::PAYLATER:

                $this->validatePaylaterProvider($issuer);

                break;

            case Method::FPX:

                $this->validateFPXIssuer($issuer);

                break;

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'Method ' . $method . ' is not supported');
        }
    }

    protected function validateFPXIssuer($issuer)
    {
        if (empty($issuer) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
            'Issuer cannot be empty for FPX');
        }

        if ((in_array($issuer, Processor\Fpx::getB2CBanks()) === false) &&
            (in_array($issuer, Processor\Fpx::getB2BBanks()) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
            'Issuer is not Valid');
        }
    }

    protected function validateUpiIssuer(string $issuer = null)
    {
        if (empty($issuer) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Issuer cannot be empty for UPI');
        }

        if (in_array($issuer, [Entity::ALL, Entity::UNKNOWN, Entity::NA], true) === true)
        {
            return;
        }

        if (IFSC::exists($issuer) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a valid Bank code');
        }
    }

    protected function validateCardIssuer(string $issuer = null)
    {
        if (empty($issuer) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Issuer cannot be empty for method card');
        }

        if ((in_array($issuer, [Entity::ALL, Entity::UNKNOWN, Entity::NA], true) === true))
        {
            return;
        }

        if (IFSC::exists($issuer) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a valid Bank code');
        }
    }

    protected function validateNetbankingIssuer(string $gateway, string $issuer = null)
    {
        if (empty($issuer) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Issuer cannot be empty for method netbanking');
        }

        if ((strtoupper($gateway) !== Entity::ALL) and
            (in_array($issuer, [Entity::ALL, Entity::UNKNOWN, Entity::NA], true) === true))
        {
            return;
        }

        $gateways = Gateway::getGatewaysForNetbankingBank($issuer);

        if (($gateway !== Entity::ALL) and
            (in_array($gateway, $gateways, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a supported Bank code for gateway ' . $gateway);
        }

        if (Netbanking::isSupportedBank($issuer) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer. ' is not a valid Bank code');
        }
    }

    protected function validateWalletIssuer(string $issuer = null)
    {
        // We convert the issuer to lower case because we convert issuer to upper case in StatusCakeProcessor
        $issuer = strtolower($issuer);

        if (Wallet::exists($issuer) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a valid wallet');
        }
    }

    protected function validatePaylaterProvider(string  $issuer = null)
    {
        $issuer = strtolower($issuer);

        if (PayLater::exists($issuer) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a valid provider');
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

        $method = $input[Entity::METHOD] ?? $this->entity->getMethod();

        $gateway = $input[Entity::GATEWAY] ?? $this->entity->getGateway();

        if (in_array($network, [Entity::ALL, Entity::UNKNOWN, Entity::NA], true) === true)
        {
            return;
        }

        if (Network::isValidNetwork($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $network . ' is not a valid network');
        }

        if ((strtolower($method) === Method::CARD) and
            (strtolower($gateway) === strtolower(Entity::ALL)))
        {
            return;
        }

        $cardNetWork = Gateway::$cardNetworkMap[$gateway];

        if ((strtolower($method) === Method::CARD) and
            (in_array($network, $cardNetWork, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                $input[Entity::NETWORK] . ' is not a valid network for gateway ' . $gateway);
        }
    }

    public function validateMethod(array $input)
    {
        $method = $input[Entity::METHOD];

        Method::validateMethod($method);

        $gateway = strtolower($input[Entity::GATEWAY]);

        if (($gateway === strtolower(Entity::ALL)) or
            ($gateway === Gateway::SHARP))
        {
            return;
        }

        if (Gateway::isMethodSupported($method, $gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway ' . $gateway . ' does not support ' . $method . ' method');
        }
    }
}
