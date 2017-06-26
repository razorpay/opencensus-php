<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal\Category;

class Validator extends Base\Validator
{
    const AMOUNTS = 'amounts';

    protected static $createRules = [
        Entity::GATEWAY          => 'required|string|max:50|custom',
        Entity::MERCHANT_ID      => 'required|alpha_num|size:14',
        Entity::TYPE             => 'required|in:sorter,filter',
        Entity::GROUP            => 'sometimes|filled|string|max:50',
        Entity::FILTER_TYPE      => 'required_if:type,filter|in:select,reject',
        Entity::LOAD             => 'required_if:type,sorter|filled|numeric|between:0,100',
        Entity::METHOD           => 'required|string|max:30',
        Entity::METHOD_TYPE      => 'sometimes|filled|string|max:10',
        Entity::ISSUER           => 'sometimes|filled|string',
        Entity::NETWORK          => 'sometimes|filled|string|max:10',
        Entity::MIN_AMOUNT       => 'sometimes|filled|integer|min:0',
        Entity::MAX_AMOUNT       => 'sometimes|filled|integer|min:1',
        Entity::EMI_DURATION     => 'sometimes_if:method,emi|integer|in:3,6,9,12,18,24',
        Entity::IINS             => 'sometimes_if:method,card,emi|filled|array|custom',
        Entity::GATEWAY_ACQUIRER => 'sometimes|filled|string',
        Entity::INTERNATIONAL    => 'sometimes|filled|boolean',
        Entity::NETWORK_CATEGORY => 'sometimes_if:type,filter|string|max:30',
        Entity::CATEGORY2        => 'sometimes_if:type,filter|filled|string|max:30|custom',
        Entity::TERMINAL_TYPE    => 'sometimes_if:type,filter|in:shared,direct'
    ];

    protected static $editRules = [
        Entity::TYPE        => 'required|in:sorter,filter',
        Entity::GROUP       => 'sometimes_if:type,filter|filled|string|max:50',
        Entity::FILTER_TYPE => 'sometimes_if:type,filter|filled|in:select,reject',
        Entity::LOAD        => 'sometimes_if:type,sorter|filled|numeric|between:0,100',
    ];

    protected static $createValidators = [
        Entity::METHOD,
        Entity::METHOD_TYPE,
        Entity::ISSUER,
        Entity::NETWORK,
        Entity::GATEWAY_ACQUIRER,
        Entity::NETWORK_CATEGORY,
        self::AMOUNTS,
    ];

    protected function validateGateway(string $attribute, string $gateway)
    {
        if (Gateway::isValidGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                        $gateway . ' is not a valid gateway');
        }
    }

    protected function validateGatewayAcquirer(array $input)
    {
        $gateway = $input[Entity::GATEWAY];

        $gatewayAcquirer = $input[Entity::GATEWAY_ACQUIRER] ?? null;

        if (empty($gatewayAcquirer) === true)
        {
            return;
        }

        if (isset(Gateway::GATEWAY_ACQUIRERS[$gateway]) === false)
        {
            return;
        }

        if (Gateway::isValidAcquirerForGateway($gatewayAcquirer, $gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                        $gatewayAcquirer . ' is not a valid gateway acquirer for ' . $gateway);
        }
    }

    protected function validateMethod(array $input)
    {
        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY];

        if (Method::isValid($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                        $method . ' is not a valid payment method');
        }

        // Verify if gateway supports given method only if the rule type is sorter
        if ($input[Entity::TYPE] === Entity::SORTER)
        {
            if (Gateway::isMethodSupported($method, $gateway) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Gateway ' . $gateway . ' does not support ' . $method . ' method');
            }
        }
    }

    protected function validateMethodType(array $input)
    {
        $method = $input[Entity::METHOD];

        if (empty($input[Entity::METHOD_TYPE]) === true)
        {
            return;
        }

        if (in_array($method, [Method::CARD, Method::EMI], true) === true)
        {
            $cardType = $input[Entity::METHOD_TYPE];

            if (Card\Type::isValidType($cardType) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Card Type: ' . $cardType . ' is not supported');
            }
        }
    }

    protected function validateIssuer(array $input)
    {
        $issuer = $input[Entity::ISSUER] ?? null;

        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY];

        switch($method)
        {
            case Method::CARD:
            case Method::EMI:

                $this->validateCardIssuer($issuer);

                break;

            case Method::NETBANKING:

                $this->validateNetbankingIssuer($gateway, $issuer);

                break;

            default:

                // For certain methods like UPI / wallet there is no concept of issuer, so
                // we don't validate if issuer is null
                if ($issuer !== null)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Issuer ' . $issuer . ' for method ' . $method . ' is not supported');
                }
        }
    }

    protected function validateCardIssuer(string $issuer = null)
    {
        if (($issuer !== null) and (IFSC::exists($issuer) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a valid bank code');
        }
    }

    protected function validateNetbankingIssuer(string $gateway, string $issuer = null)
    {
        if ($issuer === null)
        {
            if (in_array($gateway, Gateway::$netbankingGateways, true) === true)
            {
                return;
            }

            throw new Exception\BadRequestValidationFailureException(
                'issuer can be null only for shared netbanking gateways');
        }

        $gateways = Gateway::getGatewaysForNetbankingBank($issuer);

        if (in_array($gateway, $gateways, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a supported bank for gateway ' . $gateway);
        }
    }

    protected function validateNetwork(array $input)
    {
        $network = $input[Entity::NETWORK] ?? null;

        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY];

        // Don't validate if method is not card/emi or if network is null
        if ((in_array($method, [Method::CARD, Method::EMI], true) === false) or
            ($network === null))
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
            (in_array($network, $cardNetWorks, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                $network . ' is not a valid network for gateway ' . $gateway);
        }
    }

    protected function validateAmounts(array $input)
    {
        if ((empty($input[Entity::MIN_AMOUNT]) === true) or
            (empty($input[Entity::MAX_AMOUNT]) === true))
        {
            return;
        }

        $result = ($input[Entity::MIN_AMOUNT] < $input[Entity::MAX_AMOUNT]);

        if ($result === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'min_amount should be lesser than max_amount');
        }
    }

    protected function validateIins(string $attribute, array $iins)
    {
        if (is_associative_array($iins) === true)
        {
            throw new Exception\BadRequestValidationFailureException("
                iins should be sent as a numerically indexed array");
        }

        $invalidIin = array_first($iins, function ($iin)
        {
            return strlen($iin) > 6;
        });

        if (empty($invalidIin) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'iins should not be longer than 6 characters');
        }
    }

    protected function validateNetworkCategory(array $input)
    {
        if (empty($input[Entity::NETWORK_CATEGORY]) === true)
        {
            return;
        }

        list($networkCategory, $method, $gateway) = [
            $input[Entity::NETWORK_CATEGORY],
            $input[Entity::METHOD],
            $input[Entity::GATEWAY]];

        if (Category::isNetworkCategoryValid($networkCategory, $method, $gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Category provided invalid for gateway',
                Entity::NETWORK_CATEGORY,
                [$input[Entity::NETWORK_CATEGORY]]);
        }
    }

    protected function validateCategory2($attribute, $category2)
    {
        if (Category::isMerchantCategoryValid($category2) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Category :' . $category2 . 'invalid for merchant');
        }
    }
}
