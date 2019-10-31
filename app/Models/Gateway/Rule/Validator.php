<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Category;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Upi\Base\ProviderCode;

class Validator extends Base\Validator
{
    const AMOUNTS = 'amounts';

    protected static $createRules = [
        Entity::GATEWAY                 => 'required_if:type,sorter|string|max:50|custom',
        Entity::ORG_ID                  => 'sometimes|public_id',
        Entity::MERCHANT_ID             => 'required_without:org_id|alpha_num|size:14',
        Entity::PROCURER                => 'sometimes|nullable|in:razorpay,merchant',
        Entity::TYPE                    => 'required|in:sorter,filter',
        Entity::GROUP                   => 'filled|string|max:50',
        Entity::FILTER_TYPE             => 'required_unless:type,sorter|required_only_if:type,filter|in:select,reject',
        Entity::LOAD                    => 'required_unless:type,filter|required_only_if:type,sorter|numeric|between:0,100',
        Entity::GATEWAY_ACQUIRER        => 'sometimes|string|max:30',
        Entity::INTERNATIONAL           => 'filled|boolean',
        Entity::NETWORK_CATEGORY        => 'sometimes|string|max:30',
        Entity::CATEGORY                => 'sometimes_if:type,filter|string|numeric|digits:4',
        Entity::CATEGORY2               => 'sometimes_if:type,filter|string|max:30|custom',
        Entity::SHARED_TERMINAL         => 'filled|boolean',
        Entity::METHOD                  => 'required|string|max:30',
        Entity::METHOD_TYPE             => 'filled|string|max:10',
        Entity::METHOD_SUBTYPE          => 'filled|string|max:10|custom',
        Entity::ISSUER                  => 'filled|string',
        Entity::NETWORK                 => 'sometimes|string|max:10',
        Entity::MIN_AMOUNT              => 'filled|integer|min:0',
        Entity::MAX_AMOUNT              => 'filled|integer|min:1',
        Entity::EMI_DURATION            => 'required_only_if:method,emi|integer|in:3,6,9,12,18,24',
        Entity::EMI_SUBVENTION          => 'required_only_if:method,emi|in:customer,merchant',
        Entity::IINS                    => 'filled|array',
        Entity::CURRENCY                => 'filled|string|size:3|custom',
        Entity::COMMENTS                => 'filled|string|max:255',
        Entity::RECURRING               => 'filled|boolean',
        Entity::RECURRING_TYPE          => 'sometimes_if:recurring,1|in:auto,initial',
        Entity::STEP                    => 'sometimes|in:authorization,authentication',
        Entity::AUTH_TYPE               => 'sometimes',
        Entity::AUTHENTICATION_GATEWAY  => 'sometimes',
        Entity::CARD_CATEGORY           => 'sometimes',
        Entity::CAPABILITY              => 'filled|in:0,1,2',
    ];

    protected static $editRules = [
        Entity::GROUP            => 'filled|string|max:50',
        Entity::FILTER_TYPE      => 'filled|in:select,reject',
        Entity::LOAD             => 'filled|numeric|between:0,100',
        Entity::IINS             => 'filled|array',
        Entity::COMMENTS         => 'filled|string|max:255',
        Entity::RECURRING        => 'filled|boolean',
        Entity::RECURRING_TYPE   => 'sometimes_if:recurring,1|in:auto,initial',
    ];

    protected static $createValidators = [
        Entity::METHOD,
        Entity::METHOD_TYPE,
        Entity::ISSUER,
        Entity::NETWORK,
        Entity::GATEWAY_ACQUIRER,
        Entity::NETWORK_CATEGORY,
        Entity::IINS,
        self::AMOUNTS,
        Entity::RECURRING_TYPE,
    ];

    protected static $editValidators = [
        Entity::FILTER_TYPE,
        Entity::LOAD,
        Entity::IINS,
    ];

    protected function validateGateway(string $attribute, string $gateway)
    {
        Gateway::validateGateway($gateway);
    }

    protected function validateAuthenticationGateway(string $attribute, string $gateway)
    {
        Gateway::validateGateway($gateway);
    }

    protected function validateAuthType(string $attribute, string $authType)
    {
        // TODO move it to constants
        $authTypes = [
            'ivr',
            'otp',
            'headless_otp',
            '3ds',
        ];

        if (($authType !== null) and
            (in_array($authType, $authTypes, true) === false))
        {
           throw new Exception\BadRequestValidationFailureException(
                'Invalide auth type for authentication gateway');
        }
    }

    protected function validateRecurringType(array $input)
    {
        if ((isset($input['recurring']) === false) or
            (isset($input['recurring_type']) === false))
        {
                return true;
        }

        $recurringGateway = Payment\Gateway::isRecurringGateway($input['gateway']);

        if ($recurringGateway === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Recurring type can only be set for recurring Gateways');
        }
    }

    protected function validateGatewayAcquirer(array $input)
    {
        $gateway = $input[Entity::GATEWAY] ?? null;

        $gatewayAcquirer = $input[Entity::GATEWAY_ACQUIRER] ?? null;

        if ((empty($gateway) === true) or (empty($gatewayAcquirer) === true))
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

        if (Method::isValid($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                        $method . ' is not a valid payment method');
        }

        if (empty($input[Entity::GATEWAY]) === true)
        {
            return;
        }

        $gateway = $input[Entity::GATEWAY];

        // If it is a reject filter type  don't check if gateway supports method
        if (self::isRejectFilter($input) === true)
        {
            return;
        }

        if (($gateway !== Gateway::SHARP) and
            (Gateway::isMethodSupported($method, $gateway) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway ' . $gateway . ' does not support ' . $method . ' method');
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

    protected function validateMethodSubtype(string $attribute, string $subtype)
    {
        Card\SubType::checkSubType($subtype);
    }

    protected function validateIssuer(array $input)
    {
        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY] ?? null;

        //
        // skip issuer validation if gateway is sharp,
        // as sharp is test gateway and works for everything
        //
        if ((empty($gateway) === true) or ($gateway === Gateway::SHARP))
        {
            return;
        }

        switch($method)
        {
            case Method::CARD:
                $this->validateCardIssuer($input);
                break;

            case Method::EMI:
                $this->validateEmiIssuer($input);
                break;

            case Method::NETBANKING:

                $this->validateNetbankingIssuer($input);

                break;

            case Method::WALLET:

                $this->validateWalletIssuer($input);

                break;

            case Method::UPI:

                $this->validateUpiIssuer($input);

                break;

            default:

                // For certain methods like UPI there is no concept of issuer, so
                // we don't validate if issuer is null
                if (empty($input[Entity::ISSUER]) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Issuer ' . $input[Entity::ISSUER] .
                        ' for method ' . $method . ' is not supported');
                }
        }
    }

    protected function validateCardIssuer(array $input)
    {
        $issuer = $input[Entity::ISSUER] ?? null;

        if (($issuer !== null) and (IFSC::exists($issuer) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a valid bank code');
        }
    }

    protected function validateEmiIssuer(array $input)
    {
        if (self::isRejectFilter($input) === true)
        {
            return;
        }

        $gatewayToEmiBankMap = array_flip(Gateway::$emiBankToGatewayMap);

        $gateway = $input[Entity::GATEWAY];
        $issuer = $input[Entity::ISSUER] ?? null;

        if ((isset($gatewayToEmiBankMap[$gateway]) === true) and ($issuer !== $gatewayToEmiBankMap[$gateway]))
        {
            throw new Exception\BadRequestValidationFailureException(
                $issuer . ' is not a valid for emi for gateway ' . $gateway);
        }
    }

    protected function validateNetbankingIssuer(array $input)
    {
        // If it is a reject filter type skip validation
        if (self::isRejectFilter($input) === true)
        {
            return;
        }

        $issuer = $input[Entity::ISSUER] ?? null;
        $gateway = $input[Entity::GATEWAY];

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

    protected function validateWalletIssuer(array $input)
    {
        if (self::isRejectFilter($input) === true)
        {
            return;
        }

        $issuer = $input[Entity::ISSUER] ?? null;
        $gateway = $input[Entity::GATEWAY];

        if ($issuer === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'issuer cannot be null for wallet select filter rules');
        }

        if ($gateway !== Gateway::$walletToGatewayMap[$issuer])
        {
            throw new Exception\BadRequestValidationFailureException(
                'wallet issuer not valid for gateway');
        }
    }

    protected function validateUpiIssuer(array $input)
    {
        $issuer = $input[Entity::ISSUER] ?? null;

        if (($issuer !== null) and
            (ProviderCode::validateBankCode($issuer) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
               'Invalid bank code for PSP');
        }
    }

    protected function validateNetwork(array $input)
    {
        $network = $input[Entity::NETWORK] ?? null;

        $method = $input[Entity::METHOD];

        $gateway = $input[Entity::GATEWAY] ?? null;

        // Don't validate if method is not card/emi or if network is null or gateway is
        // sharp or null
        if ((in_array($method, [Method::CARD, Method::EMI], true) === false) or
            ($network === null) or
            ($gateway === Gateway::SHARP) or
            (empty($gateway) === true))
        {
            return;
        }

        // Check if netowrk is a valid card network
        if (Network::isValidNetwork($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $network . ' is not a valid network');
        }

        // If it is a reject filter type don't check if gateway supports network
        if (self::isRejectFilter($input) === true)
        {
            return;
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

    protected function validateIins(array $input)
    {
        if (empty($input[Entity::IINS]) === true)
        {
            return;
        }

        $iins = $input[Entity::IINS];

        $method = $this->entity->getMethod() ?? $input[Entity::METHOD];

        $allowedMethods = [Method::CARD, Method::EMI];

        if (in_array($method, $allowedMethods, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'iins should be sent only for card or emi rules');
        }

        if (is_associative_array($iins) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'iins should be sent as a numerically indexed array');
        }

        $invalidIin = array_first($iins, function ($iin)
        {
            return strlen($iin) != 6;
        });

        if (empty($invalidIin) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'iins should be equal to 6 characters');
        }
    }

    protected function validateNetworkCategory(array $input)
    {
        if ((empty($input[Entity::NETWORK_CATEGORY]) === true) or
            (empty($input[Entity::GATEWAY]) === true))
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
                'Category: ' . $category2 . ' invalid for merchant');
        }
    }

    protected function validateFilterType(array $input)
    {
        if ((empty($input[Entity::FILTER_TYPE]) === false) and
            ($this->entity->isFilter() === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'filter_type is editable only for filter rules');
        }
    }

    protected function validateLoad(array $input)
    {
        if ((empty($input[Entity::LOAD]) === false) and
            ($this->entity->isSorter() === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'load is editable only for sorter rules');
        }
    }

    protected function validateCurrency($attribute, $currency)
    {
        if (in_array($currency, Currency::SUPPORTED_CURRENCIES, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'currency not supported');
        }
    }

    protected static function isRejectFilter(array $input): bool
    {
        return (($input[Entity::TYPE] === Entity::FILTER) and
                ($input[Entity::FILTER_TYPE] === Entity::REJECT));
    }
}
