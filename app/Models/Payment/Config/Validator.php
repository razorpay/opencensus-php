<?php


namespace RZP\Models\Payment\Config;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Order as Order;
use RZP\Models\Payment as Payment;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Card\IIN\Repository as IINRepo;

class Validator extends Base\Validator
{
    const BANKS              = 'banks';

    const IINS               = 'iins';

    const PROVIDERS          = 'providers';

    const FLOWS              = 'flows';

    const ISSUERS            = 'issuers';

    const TYPES              = 'types';

    const NETWORKS           = 'networks';

    const WALLETS            = 'wallets';

    const METHOD             = 'method';

    const DURATIONS          = 'durations';

    const ALLOW              = 'allow';

    const CONFIG_JSON        = 'config_json';

    const LANGUAGE_CODE      = 'language_code';

    const DCC_MARKUP_PERCENTAGE = 'dcc_markup_percentage';

    const DCC_RECURRING_MARKUP_PERCENTAGE = 'dcc_recurring_markup_percentage';

    const MCC_MARKDOWN_PERCENTAGE = 'mcc_markdown_percentage';
    const INTL_BANK_TRANSFER_ACH_MCC_MARKDOWN_PERCENTAGE = 'intl_bank_transfer_ach_mcc_markdown_percentage';
    const INTL_BANK_TRANSFER_SWIFT_MCC_MARKDOWN_PERCENTAGE = 'intl_bank_transfer_swift_mcc_markdown_percentage';

    const SUPPORTED_LANGUAGE_CODE = [
        'hi',
        'en',
        'guj',
        'mar',
        'tel',
        'tam',
        'ben',
    ];

    protected static $createRules = [
        Entity::NAME           => 'required|string|max:255',
        Entity::CONFIG         => 'required|array',
        Entity::IS_DEFAULT     => 'required|boolean',
        Entity::TYPE           => 'required|string|custom',
    ];

    protected static $editRules = [
        Entity::TYPE           => 'required|string|custom',
        Entity::IS_DEFAULT     => 'required_if:type,checkout|boolean',
        Entity::ID             => 'required_if:type,checkout|string',
        Entity::CONFIG         => 'required_if:type,late_auth|array',
    ];

    protected static $deleteRules = [
        Entity::TYPE           => 'required|string|custom',
        'merchant_ids'         => 'array',
        'merchant_ids.*'       => 'filled|string|unsigned_id',
    ];

    protected static $editBulkRules = [
        Entity::CONFIG         => 'required|array',
        'merchant_ids'         => 'array',
        'merchant_ids.*'       => 'filled|string|unsigned_id',
    ];

    protected static $createBulkRules = [
        Entity::CONFIG         => 'required|array',
        Entity::IS_DEFAULT     => 'required|boolean',
        Entity::TYPE           => 'required|string|custom',
        'merchant_ids'         => 'array',
        'merchant_ids.*'       => 'filled|string|unsigned_id',
    ];

    protected static $createValidators = [
        Self::CONFIG_JSON,
    ];

    // will add a locale class once we have more number of language code to support
    protected static $localeConfigRules= [
        self::LANGUAGE_CODE    => 'required|string|custom',
    ];

    protected static $dccConfigRules = [
        self::DCC_MARKUP_PERCENTAGE   => 'required|numeric|between:0,99.99|regex:/^\d+(\.\d{1,2})?$/',
    ];

    protected static $dccRecurringConfigRules = [
        self::DCC_RECURRING_MARKUP_PERCENTAGE   => 'required|numeric|between:0,99.99|regex:/^\d+(\.\d{1,2})?$/',
    ];

    protected static $mccMarkdownConfigRules = [
        self::MCC_MARKDOWN_PERCENTAGE                           => 'required|numeric|between:0,99.99|regex:/^\d+(\.\d{1,2})?$/',
        self::INTL_BANK_TRANSFER_ACH_MCC_MARKDOWN_PERCENTAGE    => 'sometimes|numeric|between:0,99.99|regex:/^\d+(\.\d{1,2})?$/',
        self::INTL_BANK_TRANSFER_SWIFT_MCC_MARKDOWN_PERCENTAGE  => 'sometimes|numeric|between:0,99.99|regex:/^\d+(\.\d{1,2})?$/',
    ];

    protected static $editValidators = [
        Entity::CONFIG,
        self::CONFIG_JSON,
        Entity::IS_DEFAULT,
    ];

    protected static $addRestrictionsRules = [
        self::ALLOW            => 'required|array',
    ];

    protected static $cardRestrictionRules = [
        self::METHOD              =>  'required',
        self::TYPES               =>  'sometimes|array|in:credit,debit',
        self::ISSUERS             =>  'sometimes|custom',
        self::NETWORKS            =>  'sometimes|custom',
        self::IINS                =>  'sometimes|custom',
    ];

    protected static $emiRestrictionRules = [
        self::METHOD               =>  'required',
        self::TYPES                =>  'sometimes|array|in:credit,debit',
        self::ISSUERS              =>  'sometimes|array',
        self::NETWORKS             =>  'sometimes|custom',
        self::IINS                 =>  'sometimes|custom',
        self::DURATIONS            =>  'sometimes|array|in:3,6,9,12,18,24',
    ];

    protected static $upiRestrictionRules = [
        self::METHOD               =>  'required',
        self::FLOWS                =>  'sometimes|array|in:collect,intent,qr',
    ];

    protected static $netbankingRestrictionRules = [
        self::METHOD               =>  'required',
        self::BANKS                =>  'sometimes|custom',
    ];

    protected static $cardlessEmiRestrictionRules = [
        self::METHOD               =>  'required',
        self::PROVIDERS            =>  'sometimes|custom:providersForCardlessEMI',
    ];

    protected static $walletRestrictionRules = [
        self::METHOD               =>  'required',
        self::WALLETS              =>  'sometimes|custom',
    ];

    protected static $paylaterRestrictionRules = [
        self::METHOD              =>  'required',
        self::PROVIDERS           =>  'sometimes|custom:providersForPaylater',
    ];

    protected static array $fetchPaymentConfigForCheckoutRules = [
      'config_id'   => 'sometimes|filled|public_id',
    ];

    const PROPERTIES_TO_VALIDATE_FOR_CARD = [
        self::ISSUERS, self::TYPES, self::NETWORKS, self::IINS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_EMI = [
        self::ISSUERS, self::TYPES, self::NETWORKS, self::IINS, self::DURATIONS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_NETBANKING = [
        self::BANKS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_WALLET = [
        self::WALLETS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_UPI = [
        self::FLOWS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_CARDLESS_EMI = [
        self::PROVIDERS,
    ];

    const PROPERTIES_TO_VALIDATE_FOR_PAYLATER = [
        self::PROVIDERS,
    ];

    protected function validateConfig(array $input)
    {
        if (($input['type'] === Type::CHECKOUT) and
            (isset($input['config']) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Config field is not required for type checkout');
        }
    }

    protected function validateConfigJson(array $input)
    {
        if ($input['type'] === Type::LOCALE)
        {
            $this->validateInput('locale_config', $input['config']);
        }

        if ($input['type'] === Type::DCC)
        {
            $this->validateInput('dcc_config', $input['config']);
        }

        if ($input['type'] === Type::DCC_RECURRING)
        {
            $this->validateInput('dcc_recurring_config', $input['config']);
        }

        if ($input['type'] === Type::MCC_MARKDOWN)
        {
            $this->validateInput('mcc_markdown_config', $input['config']);
        }

        if (($input['type'] === Type::LATE_AUTH) and
            (isset($input['config']) === true))
        {

            $config = $input['config'];

            if (isset($config['capture']) === false) {
                throw new Exception\BadRequestValidationFailureException(
                    'Config Json format is not correct, capture is required');
            }

            if (in_array($config['capture'], ['automatic', 'manual'], true) === false) {
                throw new Exception\BadRequestValidationFailureException(
                    'Config Capture value can either be automatic or manual');
            }

            if (isset($config['capture_options'], $config['capture_options']['refund_speed']) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Config Json format is not correct, refund speed is required');
            }

            if (in_array($config['capture_options']['refund_speed'], ['normal', 'optimum'], true) === false) {
                throw new Exception\BadRequestValidationFailureException(
                    'Config refund speed value can either be normal or optimum');
            }

            if (($config['capture'] === 'automatic') and
                (isset($config['capture_options']['automatic_expiry_period'],
                    $config['capture_options']['manual_expiry_period']) === true))
            {
                if ($config['capture_options']['manual_expiry_period'] < $config['capture_options']['automatic_expiry_period']) {
                    throw new Exception\BadRequestValidationFailureException(
                        'Manual Expiry Period should be greater than or equal to automatic expiry period passed (default automatic expiry period is 20 mins)');
                }
            }

            if (isset($config['capture_options']['automatic_expiry_period']) === true)
            {
                if ((($config['capture_options']['automatic_expiry_period'] >= 12) and
                        ($config['capture_options']['automatic_expiry_period'] <= 7200)) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Config Automatic duration should in between 12 minutes and 5 days');
                }
            }

            if (isset($config['capture_options']['manual_expiry_period']) === true)
            {
                if ((($config['capture_options']['manual_expiry_period'] >= 12) and
                        ($config['capture_options']['manual_expiry_period'] <= 7200)) === false) {
                    throw new Exception\BadRequestValidationFailureException(
                        'Config Manual duration should in between 12 minutes and 5 days');
                }
            }

            if (($config['capture'] === 'manual') and
                (isset($config['capture_options']['manual_expiry_period']) === false))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Config Manual duration should be set when capture is manual');
                }
        }
        if($input['type'] === Type::CONVENIENCE_FEE)
        {
            $this->validateConvenienceFeeConfig($input['config']);
        }
    }

    protected function validateConvenienceFeeConfig($config)
    {
        $convenienceFeeConfig = $config;

        if(isset($config['rules']) === false) {
            return;
        }

        $this->validateInputTypeAndExtraFields("convenience_fee.",
            [
                'message'=>['string', 120],
                'label'  =>['string', 20],
                'rules'  =>['array', 10]
            ],
            $convenienceFeeConfig
        );

        if(isset($convenienceFeeConfig['rules']) === true)
        {
            foreach ($convenienceFeeConfig['rules'] as $rule)
            {
                $this->validateConfigRule($rule);
            }
        }
    }

    public function validateConfigRule($rule)
    {
        $this->validateRequiredFields('convenience_fee_config.rules.', ['method', 'fee'], $rule);

        if(is_string($rule['method']) and in_array($rule['method'], Entity::FEE_CONFIG_METHODS) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                'convenience_fee_config.rules.'.$rule['method'],
                null,
                "{$rule['method']} is not a valid method"
            );
        }

        if($rule['method'] === 'card' and
            isset($rule['card.type']) === true)
        {
            $this->validateInputTypeAndExtraFields(
                'convenience_fee_config.rules.',
                ['method' => ['string'], 'card.type' => ['array', 3], 'fee'=>['array']],
                $rule
            );
        }
        else
        {
            $this->validateInputTypeAndExtraFields(
                'convenience_fee_config.rules.',
                ['method' => ['string'], 'fee'=>['array']],
                $rule
            );
        }

        $this->validateFee($rule['fee']);
    }


    //Fee related validations for convenience fee config
    public function validateFee($fee)
    {
        if(isset($fee['flat_value']) === true)
        {
            $this->validateInputTypeAndExtraFields(
                'convenience_fee_config.rules.',
                ['payee' => ['string'], 'flat_value'=>['integer']],
                $fee
            );
            $this->validateRequiredFields("convenience_fee_config.rules.fee.",['payee', 'flat_value'],$fee);

            if($fee['flat_value'] < 0)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                    'convenience_fee_config.rules.fee.flat_value',
                    null,
                    'The value for this parameter cannot be less than 0'
                );
            }
        }
        else
        {
            $this->validateInputTypeAndExtraFields(
                'convenience_fee_config.rules.',
                ['payee' => ['string'], 'percentage_value'=>['string']],
                $fee
            );

            $this->validateRequiredFields("convenience_fee_config.rules.fee.",['payee', 'percentage_value'],$fee);

            if((is_numeric($fee['percentage_value']) === true and
                $this->validatePercentage(floatval($fee['percentage_value'])) === false) or
                is_numeric($fee['percentage_value']) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                    'convenience_fee_config.rules.fee.percentage_value',
                    null,
                    'Incorrect format provided for the parameter. Please check the valid format'
                );
            }
            else if((is_numeric($fee['percentage_value']) === true and
                $this->validatePercentage(floatval($fee['percentage_value'])) === true) and
                (floatval($fee['percentage_value']) < 0 or floatval($fee['percentage_value']) > 100 ))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                    'convenience_fee_config.rules.fee.percentage_value',
                    null,
                    'The value for this parameter cannot be less than 0 or greater than 100'
                );
            }
        }

        if( in_array($fee['payee'], Entity::FEE_PAYEE) === false )
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                'convenience_fee_config.rules.fee.payee',
                null,
                "{$fee['payee']} is not a valid value for this parameter."
            );
        }
    }

    public function validatePercentage($number,$decimal=2) : bool
    {
        $multiplicationFactor=pow(10,$decimal);

        if((int)($number*$multiplicationFactor) == $number*$multiplicationFactor)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    //This function will take input list of fields and correspondingly type and length
    // and validates with actual value
    protected function validateInputTypeAndExtraFields(string $fieldPrefix, $rules, $convenienceFeeConfig)
    {
        $extraFields = [];
        foreach($convenienceFeeConfig as $key => $value)
        {
            if(isset($rules[$key]) === true)
            {
                $inputType = gettype($value);

                if($inputType !== $rules[$key][0])
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                        $fieldPrefix.$key,
                        null,
                        "{$key} value should be {$rules[$key][0]}"
                    );
                }
                else if(isset($rules[$key][1]) === true)
                {
                    $length = $inputType === 'string' ? strlen($value) : sizeof(($value));

                    if($length > $rules[$key][1])
                    {
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                            $fieldPrefix.$key,
                            null,
                            "{$key} cannot be greater then {$rules[$key][1]} characters"
                        );
                    }
                }
            }
            else {
                $extraFields[] = $fieldPrefix.$key;
            }
        }

        if(sizeof($extraFields) !== 0)
        {
            throw new Exception\ExtraFieldsException($extraFields);
        }
    }

    //This field validates whether field is required(applicable only for convenience field config)
    public function validateRequiredFields($fieldPrefix, $fields, $rule)
    {
        foreach($fields as $field)
        {
            if(isset($rule[$field]) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                    $fieldPrefix.$field,
                    null,
                    "The order could not be processed as it is missing required information"
                );
            }
        }
    }
    protected function validateIssuers($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'issuers should be an array');
        }

        foreach ($input as $issuer)
        {
            if ((IFSC::exists($issuer) === false)){
                throw new Exception\BadRequestValidationFailureException(
                    $issuer . ' is not a valid bank code');
            }
        }
    }

    protected function validateNetworks($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'networks should be an array');
        }

        foreach ($input as $network)
        {
            if (Network::isValidNetwork($network) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    $network . ' is not a valid network');
            }
        }

    }

    protected function validateWallets($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'wallets should be an array');
        }

        foreach ($input as $wallet)
        {
            Wallet::validateExists($wallet);
        }
    }

    protected function validateBanks($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'banks should be an array');
        }

        $supportedBanks = Netbanking::getSupportedBanks();

        foreach ($input as $bank)
        {
            if (in_array($bank, $supportedBanks, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    $bank . ' is not a valid bank');
            }
        }
    }

    protected function validateProvidersForCardlessEMI($attribute ,$input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'providers should be an array');
        }

        foreach ($input as $provider)
        {
            if (CardlessEmi::exists($provider) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provider is not supported for cardless emi '.$provider,
                    'provider'
                );
            }

        }

    }

    protected function validateProvidersForPaylater($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'providers should be an array');
        }

        foreach ($input as $provider)
        {
            if (PayLater::exists($provider) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provider is not supported for Pay Later '.$provider,
                    'provider'
                );
            }
        }
    }

    protected function validateIins($attribute, $input)
    {
        if (is_array($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'iins should be an array');
        }

        foreach ($input as $iin)
        {
            if (strlen($iin) !== 6)
            {
                throw new Exception\BadRequestValidationFailureException(
                  'iin length should be 6'
                );
            }

            $iinEntity = (new IINRepo())->find($iin);

            if (isset($iinEntity) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'iin provided does not exist '.$iin
                );
            }
        }
    }

    public function validatePaymentForConfig($input, $merchant)
    {
        $order_id = $input[Payment\Entity::ORDER_ID];

        $order_repo = new Order\Repository();

        $order = $order_repo->findByPublicIdAndMerchant($order_id, $merchant);

        $config = null;

        $config_repo = new Repository();

        if (isset($order->checkout_config_id) === true)
        {
            $config = $config_repo->findByPublicIdAndMerchant('config_'.$order->checkout_config_id, $merchant);
        }
        else
        {
            $config =$config_repo->fetchDefaultConfigByMerchantIdAndType($merchant->getId(), Type::CHECKOUT);
        }

        if ($config === null or isset($config->restrictions) === false)
        {
            return;
        }

        $restrictions = json_decode($config->restrictions, true);

        $validRestrictions = $this->getRestrictionForMethod($restrictions, $input[self::METHOD]);

        if (empty($validRestrictions))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_METHOD_NOT_ALLOWED_IN_CONFIG, "method", $input[self::METHOD],
                'Method is not allowed in the config'
            );
        }

        $validateMethod = 'validateFor' . studly_case($input[self::METHOD]);

        foreach ($validRestrictions as $restriction)
        {
            $result = $this->$validateMethod($restriction, $input);

            if ($result === true)
            {
                return true;
            }
        }

        throw new Exception\BadRequestValidationFailureException(
            'The following payment method is not supported for this transaction'
        );
    }

    private function getRestrictionForMethod($restrictions, $method)
    {
        $validRestrictions = [];

        foreach ($restrictions as $key => $restriction)
        {
            if ($restriction[self::METHOD] === $method)
            {
                $validRestrictions[] = $restriction;
            }
        }

        return $validRestrictions;
    }

    private function validateForCard(array $restriction, $input)
    {
        if (isset($input['card']['number']) === false)
        {
            if (isset($input[Payment\Entity::TOKEN]) === true)
            {
                // not supported for saved card as of now
                return true;
            }

            throw new Exception\BadRequestException('BAD_REQUEST_ERROR', 'card number',
                     null, 'either card number or token should be available');
        }

        $cardNumber = $input['card']['number'];

        $iinRepo = new IINRepo();

        $iinEntity = $iinRepo->find(substr($cardNumber, 0, 6));

        if (isset($iinEntity) === false)
        {
            throw new Exception\BadRequestException('BAD_REQUEST_ERROR', 'iin', null, 'iin not found');
        }

        foreach (self::PROPERTIES_TO_VALIDATE_FOR_CARD as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($iinEntity, $restriction);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForEmi(array $restriction, $input)
    {
        if (isset($input['card']['number']) === false)
        {
            if (isset($input[Payment\Entity::TOKEN]) === true)
            {
                // not supported for saved card as of now
                return true;
            }

            throw new Exception\BadRequestException('BAD_REQUEST_ERROR', 'card number',
                null, 'either card number or token should be available');
        }

        $cardNumber = $input['card']['number'];


        $durations = $input['emi_duration'];

        $iinRepo = new IINRepo();

        $iinEntity = $iinRepo->find(substr($cardNumber, 0, 6));

        if (isset($iinEntity) === false)
        {
            throw new Exception\BadRequestException('BAD_REQUEST_ERROR', 'iin', null, 'iin not found');
        }

        foreach (self::PROPERTIES_TO_VALIDATE_FOR_EMI as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            if ($property === self::DURATIONS)
            {
                $validateResult = $this->$validateMethod($restriction, $durations);
            }
            else
            {
                $validateResult = $this->$validateMethod($iinEntity, $restriction);
            }

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForNetbanking(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_NETBANKING as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($restriction, $input);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForWallet(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_WALLET as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($restriction, $input);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForUpi(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_UPI as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($restriction, $input);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForCardlessEmi(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_CARDLESS_EMI as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($input, $restriction);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validateForPaylater(array $restriction, $input)
    {
        foreach (self::PROPERTIES_TO_VALIDATE_FOR_PAYLATER as $property)
        {
            $validateMethod = 'validateProperty' . studly_case($property);

            $validateResult = $this->$validateMethod($input, $restriction);

            if ($validateResult === false)
            {
                return false;
            }
        }

        return true;
    }

    private function validatePropertyIssuers($iinEntity, $restriction)
    {
        if (isset($restriction[self::ISSUERS]) === false)
        {
            return;
        }
        $allowedIssuers = $restriction[self::ISSUERS];

        $paymentIssuer = $iinEntity->issuer;

        if (in_array($paymentIssuer, $allowedIssuers) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyNetworks($iinEntity, $restriction)
    {
        if (isset($restriction[self::NETWORKS]) === false)
        {
            return;
        }

        $allowedNetworks = $restriction[self::NETWORKS];

        $paymentNetwork  = $iinEntity->network;

        if (in_array($paymentNetwork, $allowedNetworks) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyTypes($iinEntity, $restriction)
    {
        if (isset($restriction[self::TYPES]) === false)
        {
            return;
        }

        $allowedTypes = $restriction[self::TYPES];

        $paymentType  = $iinEntity->type;

        if (in_array($paymentType, $allowedTypes) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyIins($iinEntity, $restriction)
    {
        if (isset($restriction[self::IINS]) === false)
        {
            return;
        }

        $allowedIins = $restriction[self::IINS];

        $paymentIin  = $iinEntity->iin;

        if (in_array($paymentIin, $allowedIins) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyDurations($restriction, $paymentEmiDuration)
    {
        if (isset($restriction[self::DURATIONS]) === false)
        {
            return;
        }

        $allowedDurations = $restriction[self::DURATIONS];

        if (in_array($paymentEmiDuration, $allowedDurations) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyBanks($restriction, $input)
    {
        if (isset($restriction[self::BANKS]) === false)
        {
            return;
        }

        $allowedBanks = $restriction[self::BANKS];

        $paymentBank = $input['bank'];

        if (in_array($paymentBank, $allowedBanks) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyWallets($restriction, $input)
    {
        if (isset($restriction[self::WALLETS]) === false)
        {
            return;
        }

        $allowedWallets = $restriction[self::WALLETS];

        $paymentWallet = $input['wallet'];

        if (in_array($paymentWallet, $allowedWallets) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyProviders($restriction, $input)
    {
        if (isset($restriction[self::PROVIDERS]) === false)
        {
            return;
        }

        $allowedProviders = $restriction[self::PROVIDERS];

        $paymentProvider = $input['provider'];

        if (in_array($paymentProvider, $allowedProviders) === false)
        {
            return false;
        }

        return true;
    }

    private function validatePropertyFlows($restriction, $input)
    {
        if (isset($restriction[self::FLOWS]) === false)
        {
            return;
        }

        $allowedFlows = $restriction[self::FLOWS];

        if (isset($input['upi']) === false or isset($input['upi']['flow']) === false)
        {
            return false;
        }

        $paymentFlow = $input['upi']['flow'];

        if (in_array($paymentFlow, $allowedFlows) === false)
        {
            return false;
        }

        return true;
    }

    public function validateRestrictionJson($allow, $merchant)
    {
        if (gettype($allow) !== 'array')
        {
            throw new Exception\BadRequestValidationFailureException(
                'allow field should be array'
            );
        }

        $merchantMethods = $merchant->methods;

        foreach ($allow as $item)
        {
            if (isset($item[self::METHOD]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'method must be set for restriction'
                );
            }

            if (Method::isValid($item[self::METHOD]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid payment method given ',
                    'method');
            }
            if ($merchantMethods->isMethodEnabled($item[self::METHOD]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'method is not enabled for the merchant'
                );
            }

            $this->validateInput(studly_case($item[self::METHOD]).'_restriction', $item);
        }
    }

    protected function validateType($attribute, $input)
    {
        $isTypeSupported = (new Type())->isConfigTypeSupported($input);

        if ($isTypeSupported === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The selected type is invalid.');
        }
    }

    protected function validateIsDefault($input)
    {
        if (($input['type'] !== Type::CHECKOUT) and
            (isset($input['is_default']) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Is default field is not required for type '.$input['type']);
        }
    }

    public function validateLanguageCode($attributes, $input)
    {
        if (in_array($input, self::SUPPORTED_LANGUAGE_CODE, true) === true)
        {
            return true;
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'Language code is not supported');
        }
    }
}
