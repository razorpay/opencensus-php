<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Card;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\Wallet;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY          => 'required|string|max:50|custom',
        Entity::MERCHANT_ID      => 'required|alpha_num|size:14',
        Entity::METHOD           => 'required|string|max:30',
        Entity::CARD_TYPE        => 'sometimes|filled|string|max:10',
        Entity::NETWORK          => 'sometimes|filled|string|max:10',
        Entity::CATEGORY         => 'sometimes|filled|string|max:4',
        Entity::GATEWAY_ACQUIRER => 'sometimes|filled|string|custom',
        Entity::INTERNATIONAL    => 'sometimes|filled|boolean',
        Entity::ISSUER           => 'sometimes|filled|string',
        Entity::LOAD             => 'required|integer|min:0|max:10000'
    ];

    protected static $createValidators = [
        Entity::METHOD,
        Entity::CARD_TYPE,
        Entity::ISSUER,
        Entity::NETWORK
    ];

    public function validateGateway(string $attribute, string $gateway)
    {
        if (Gateway::isValidGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                        $gateway . ' is not a valid gateway');
        }
    }

    public function validateGatewayAcquirer(string $attribute, string $gatewayAcquirer)
    {
        if (Gateway::isValidGatewayAcquirer($gatewayAcquirer) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                        $gatewayAcquirer . ' is not a valid gateway acquirer');
        }
    }

    public function validateMethod(array $input)
    {
        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY];

        if (Method::isValid($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                        $method . 'is not a valid payment method');
        }

        if (Gateway::isMethodSupported($method, $gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway ' . $gateway . ' does not support ' . $method . ' method');
        }
    }

    public function validateCardType(array $input)
    {
        $method = $input[Entity::METHOD];

        if (in_array($method, [Method::CARD, Method::EMI], true) === true)
        {
            $cardType = $input[Entity::CARD_TYPE];

            if ($cardType === Entity::ALL)
            {
                return;
            }

            if (Card\Type::isValidType($cardType) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Card Type: ' . $cardType . ' is not supported');
            }
        }
    }

    public function validateIssuer(array $input)
    {
        $issuer = $input[Entity::ISSUER];

        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY];

        switch($method)
        {
            case Method::CARD:
            case Method::EMI:

                $this->validateCardIssuer($method, $issuer);

                break;

            case Method::NETBANKING:

                $this->validateNetbankingIssuer($gateway, $method, $issuer);

                break;

            case Method::WALLET:

                $this->validateWalletIssuer($method, $issuer);

                break;

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'Method ' . $method . ' is not supported');
        }
    }

    protected function validateCardIssuer(string $method, string $issuer = null)
    {
        if ($issuer === Entity::ALL)
        {
            return;
        }

        if (IFSC::exists($issuer) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer. ' is not a valid Bank code');
        }
    }

    protected function validateNetbankingIssuer(string $gateway, string $method, string $issuer = null)
    {
        if ($issuer === Entity::ALL)
        {
            if (in_array($gateway, Netbanking::$netbankingGateways, true) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "issuer can be all only for shared netbanking gateways");
            }
        }

        $gateways = Gateway::getGatewaysForNetbankingBank($issuer);

        if (in_array($gateway, $gateways, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer .' is not a supported Bank code for gateway ' . $gateway);
        }

        if (Netbanking::isSupportedBank($issuer) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer. ' is not a valid Bank code');
        }
    }

    protected function validateWalletIssuer(string $method, string $issuer = null)
    {
        if (Wallet::exists($issuer) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a valid Wallet');
        }
    }

    protected function validateNetwork(array $input)
    {
        $network = $input[Entity::NETWORK] ?? null;

        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY];

        // For methods apart from card / emi network can be empty
        if (in_array($method, [Method::CARD, Method::EMI], true) === false)
        {
            if (empty($network) === true)
            {
                return;
            }
        }

        // Return if netowrk is ALL for card payment method
        if ($network === Entity::ALL)
        {
            return;
        }

        // Check if netowrk is a valid card network
        if (Network::isValidNetwork($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $network . ' is not a valid network');
        }

        // Checks if card network is supported by gateway
        $cardNetWorks = Gateway::$cardNetworkMap[$gateway];

        if (($method === Method::CARD) and
            (in_array($network, $cardNetWork, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                $network . ' is not a valid network for gateway ' . $gateway);
        }
    }
}
