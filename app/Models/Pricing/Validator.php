<?php

namespace RZP\Models\Pricing;

use RZP\Base;
use RZP\Exception;
use RZP\Constants\Procurer;
use RZP\Constants\Product;
use RZP\Constants\TokenPricing;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Card\Network;
use RZP\Models\Card\SubType;
use RZP\Models\Card\Type as CardType;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Payout;
use RZP\Models\Settlement;
use RZP\Models\Transfer;
use RZP\Models\FundAccount;
use RZP\Models\FundTransfer;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Pricing;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Payment\Processor\App as AppMethod;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccountStatement\Channel as BASChannel;
use RZP\Models\Payment\Processor\IntlBankTransfer as IntlBankTransferMethod;

class Validator extends Base\Validator
{
    const ALLOWED_AUTH_TYPE_FOR_BANKING_PRODUCT = [BasicAuth\Type::PRIVATE_AUTH, BasicAuth\Type::PROXY_AUTH];

    protected static $addPlanRuleRules = [
        Entity::PRODUCT                 => 'sometimes|string|custom',
        Entity::FEATURE                 => 'sometimes|alpha_dash',
        Entity::GATEWAY                 => 'sometimes',
        Entity::PROCURER                => 'sometimes|nullable|in:razorpay,merchant',
        Entity::PLAN_NAME               => 'sometimes',
        Entity::APP_NAME                => 'sometimes|nullable|string',
        Entity::PAYMENT_METHOD          => 'required_unless:feature,refund,optimizer,payment,affordability_widget|nullable|string',
        Entity::PAYMENT_METHOD_TYPE     => 'sometimes|nullable',
        Entity::PAYMENT_METHOD_SUBTYPE  => 'sometimes_if:payment_method,card,emandate,upi,fund_transfer|nullable',
        Entity::PAYMENT_NETWORK         => 'sometimes|nullable|string',
        Entity::PAYMENT_ISSUER          => 'sometimes|nullable|max:255',
        Entity::EMI_DURATION            => 'sometimes_if:payment_method,emi|nullable|integer|in:2,3,6,9,12,18,24',
        Entity::AUTH_TYPE               => 'sometimes|nullable',
        Entity::INTERNATIONAL           => 'sometimes|in:0,1',
        Entity::RECEIVER_TYPE           => 'sometimes_if:payment_method,card,upi|nullable|in:qr_code,vpa,pos,credit',
        Entity::AMOUNT_RANGE_ACTIVE     => 'sometimes|in:0,1',
        Entity::AMOUNT_RANGE_MIN        => 'required_only_if:amount_range_active,1|integer|nullable|max:500000000000',
        Entity::AMOUNT_RANGE_MAX        => 'required_only_if:amount_range_active,1|integer|nullable|min:100|max:500000000000', // max 500 cr
        Entity::PERCENT_RATE            => 'sometimes|integer|max:20000',
        Entity::FIXED_RATE              => 'sometimes|integer|max:2500000',
        Entity::MIN_FEE                 => 'sometimes|integer|max:100000',
        Entity::MAX_FEE                 => 'sometimes|nullable|integer|min:1|max:100000',
        Entity::TYPE                    => 'sometimes|string|custom',
        Entity::ACCOUNT_TYPE            => 'required_only_if:product,banking|filled|custom',
        Entity::CHANNEL                 => 'required_if:account_type,direct|filled|custom',
        Entity::FEE_BEARER              => 'sometimes|in:platform,customer',
        Entity::PAYOUTS_FILTER          => 'sometimes_if:product,banking',
        Entity::IS_BUY_PRICING_ALLOWED  => 'sometimes',
        Entity::FEE_MODEL               => 'sometimes|nullable|in:prepaid,postpaid',
    ];

    protected static $editPlanRuleRules = [
        Entity::PERCENT_RATE        => 'sometimes|integer|max:10000',
        Entity::FIXED_RATE          => 'sometimes|integer|max:2500000',
        Entity::MIN_FEE             => 'sometimes|integer|max:100000',
        Entity::MAX_FEE             => 'sometimes|nullable|integer|min:1|max:100000',
        Entity::FEE_BEARER          => 'sometimes|in:platform,customer',
        Entity::PROCURER            => 'sometimes',
        Entity::CHANNEL             => 'sometimes',
        Entity::FEE_MODEL           => 'sometimes|nullable|in:prepaid,postpaid'
    ];

    protected static $pricingPlansSummaryRules = [
        Entity::TYPE      => 'sometimes|string|in:pricing,commission',
        Entity::PLAN_NAME => 'sometimes|string',
        Entity::PLAN_ID   => 'sometimes|string|size:14',
        Fetch::COUNT      => 'sometimes|integer|min:0',
        Fetch::SKIP       => 'sometimes|integer|min:0',
    ];

    protected static $addPlanRuleValidators = [
        'addBuyPricingTypeRule',
        'addPlanRuleRate',
        'addPlanRuleCard',
        'addPlanRuleEmi',
        'addPlanRuleNB',
        'addPlanRuleFundAccountValidation',
        'addPlanRuleEmandateOrNach',
        'addPlanRulePaymentNetwork',
        'addPlanRuleInternational',
        'addPlanRuleAmountRange',
        'addPlanRuleFeature',
        'addPlanRuleAppName',
        'addPlanRulePricingMethod',
        'addPlanRulePricingMethodType',
        'addPlanRuleMinAndMaxFee',
        'addPlanRulePayoutFundTransfer',
        'addPlanRuleRefund',
        'addPlanRuleAuthType',
        'addPlanRuleProcurer',
        'addPlanRulePaymentIssuer',
        'addPlanRuleGateway',
        'addPlanRuleUpiSubType'
        // Skipped for now as it blocks the creation of 0-pricing rules.
        // 'addPlanRuleBankTransfer',
    ];

    protected static $fetchRules = [
        Entity::TYPE   => 'sometimes|string|in:pricing,commission',
    ];

    protected static $editPlanRuleValidators = [
        'addPlanRuleRate',
        'addPlanRuleMinAndMaxFee',
        'editPlanRuleProcurer',
        'editPlanRuleChannel'
    ];

    protected static $createPlanRules = [
        Entity::PLAN_NAME   => 'required|string|max:255'
    ];

    protected static $createPlanNameRules = [
        Entity::PLAN_NAME   => 'required|alpha_num|max:255'
    ];

    protected static $createBulkPricingRules = [
        Entity::PLAN_NAME   => 'required|string|max:255',
        Entity::RULES       => 'required|array|min:1',
    ];

    protected static $buyPricingCostRules = [
        'terminals'               => 'required|array|min:1',
        'terminals.*.terminal_id' => 'required|alpha_num',
        'terminals.*.plan_id'     => 'required|alpha_num',
        'terminals.*.gateway'     => 'required|string',
        'payment'                 => 'required|array',
    ];

    protected function validatePlanName($input)
    {
        // If no plan name, then set it to null
        $planInput[Entity::PLAN_NAME] = $input[Entity::PLAN_NAME] ?? null;

        $this->validateInput('createPlan', $planInput);

        if ($input[Entity::TYPE] !== Type::BUY_PRICING)
        {
            $this->validateInput('createPlanName', $planInput);
        }
    }

    protected function validateAddPlanRuleFeature($input)
    {
        if (empty($input[Pricing\Entity::FEATURE]))
        {
            return;
        }

        Pricing\Feature::validateFeature($input[Pricing\Entity::FEATURE]);
    }

    protected function validateAddPlanRuleAppName($input)
    {
        if (empty($input[Pricing\Entity::APP_NAME]) === true)
        {
            return;
        }

        // check for valid internal app name, for now only xpayroll is allowed
        if($input[Pricing\Entity::APP_NAME] === 'xpayroll')
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException(
            $input[Pricing\Entity::APP_NAME].'it is an invalid app name');
    }

    protected function validateAddPlanRuleProcurer($input)
    {
        if (empty($input[Pricing\Entity::PROCURER]) === true)
        {
            return;
        }

        if ($input[Pricing\Entity::FEATURE] !== Feature::OPTIMIZER)
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException(
            'procurer is not required when feature is optimizer.');
    }


    protected function validateAddPlanRuleGateway($input)
    {
        if (isset($input[Entity::GATEWAY]) === false)
        {
            return;
        }

        if ($input[Entity::TYPE] === Type::BUY_PRICING)
        {
            if (in_array($input[Entity::PAYMENT_METHOD], Payment\Method::getAllPaymentMethods()) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'invalid method sent for buy pricing: '. $input[Entity::PAYMENT_METHOD]);
            }

            if (BuyPricing::isValidBuyPricingGateway($input[Entity::PAYMENT_METHOD], $input[Entity::GATEWAY]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'invalid gateway '. $input[Entity::GATEWAY] .' sent for buy pricing method '. $input[Entity::PAYMENT_METHOD]);
            }

        }
    }

    protected function validateAddPlanRuleUpiSubType($input)
    {
        if ((isset($input[Entity::PAYMENT_METHOD]) === true) and
            ($input[Entity::PAYMENT_METHOD] === Payment\Method::UPI) and
            (empty(($input[Entity::PAYMENT_METHOD_SUBTYPE])) === false))
        {
            if ((in_array($input[Entity::PAYMENT_METHOD_SUBTYPE], [Payment\RecurringType::INITIAL, Payment\RecurringType::AUTO])) === false)
            {
                app('trace')->info(
                    TraceCode::UPI_RECURRING_PRICING_RULE_ERROR,
                    ['merchantId' => $this->entity->getMerchantId(),
                        'step'    => 'Failed during validateAddPlanRuleUpiSubType',
                        'values'  => $input
                    ]
                );

                throw new Exception\BadRequestValidationFailureException(
                        'Only null (one-time payments) or initial or auto value is allowed for sub type field in UPI.');
            }
        }
    }

    protected function validateAddPlanRulePaymentIssuer($input)
    {
        if (isset($input[Entity::PAYMENT_ISSUER]) === false)
        {
            return;
        }

        $validMethods = [
            Payment\Method::CARD,
            Payment\Method::EMI,
            Payment\Method::CARDLESS_EMI,
            Payment\Method::EMANDATE,
            Payment\Method::PAYLATER,
            Payment\Method::NACH,
        ];

        if (in_array($input[Entity::PAYMENT_METHOD], $validMethods) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $input[Entity::PAYMENT_ISSUER] .' is not required for method: '. $input[Entity::PAYMENT_METHOD]);
        }

        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::CARDLESS_EMI)
        {
            if (CardlessEmi::exists($input[Entity::PAYMENT_ISSUER]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provider selected for cardless emi should be valid');
            }
        }

        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::PAYLATER)
        {
            if (Payment\Processor\PayLater::exists($input[Entity::PAYMENT_ISSUER]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provider selected for paylater should be valid');
            }
        }
    }

    protected function validateAddBuyPricingTypeRule(array $input)
    {
        $isBuyPricingAllowed = $input[Entity::IS_BUY_PRICING_ALLOWED] ?? false;

        if ($isBuyPricingAllowed === false and $input[Entity::TYPE] === Type::BUY_PRICING)
        {
            throw new Exception\BadRequestValidationFailureException(
                'type buy pricing is not permitted');
        }
    }

    protected function validateAddPlanRulePricingMethod(array $input)
    {
        $feature = Pricing\Feature::PAYMENT;

        if (empty($input[Pricing\Entity::FEATURE]) === false)
        {
            $feature = $input[Pricing\Entity::FEATURE];
        }

        $method = $input[Pricing\Entity::PAYMENT_METHOD];

        switch ($feature)
        {
            case Pricing\Feature::PAYMENT:

                // throw error if procurer is not merchant and method is null
                if (($input[Entity::PROCURER] !== "merchant") and (is_null($method) === true))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'The payment method field is required for feature payment if procurer is not merchant.');
                }

                if (is_null($method) === false)
                {
                    Payment\Method::validateMethod($method);
                }

                break;

            case Pricing\Feature::PAYOUT:
                Payout\Method::validateMethod($method);

                break;

            case Pricing\Feature::TRANSFER:
                Transfer\ToType::validateDestination($method);

                break;

            case Pricing\Feature::FUND_ACCOUNT_VALIDATION:
                FundAccount\Validation\FundAccountType::validate($method);

                break;

            case Pricing\Feature::ESAUTOMATIC:
                Payment\Method::validateEsMethod($method);

                break;

            case Pricing\Feature::REFUND:
                Payment\Refund\Validator::validateInstantRefundPricingMethod($method);

                break;
        }
    }

    protected function validateAddPlanRulePricingMethodType(array $input)
    {
        // Valid pricing methods for which Payment method type can be added
        $validPricingMethods = [
            Payment\Method::CARD,
            Payment\Method::EMI,
            Payment\Method::EMANDATE,
            Payout\Method::FUND_TRANSFER,
            Payment\Method::NACH
        ];

        if ($input[Entity::FEATURE] === Feature::REFUND)
        {
            // Valid pricing methods for which payment method type can be added (in refunds case - mode)
            $validPricingMethods = [
                null,
                Payment\Method::CARD,
                Payment\Method::UPI,
                Payment\Method::NETBANKING,
            ];
        }

        if (empty($input[Entity::PAYMENT_METHOD_TYPE]) === false)
        {
            $pricingMethod = $input[Entity::PAYMENT_METHOD];

            if (in_array($pricingMethod, $validPricingMethods, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The payment method type field may be sent only when payment method is ' .
                    implode('/', $validPricingMethods));
            }
        }
    }

    protected function validateAddPlanRuleFundAccountValidation($input)
    {
        if ((isset($input[Entity::FEATURE]) === true) and
            $input[Entity::FEATURE] === Pricing\Feature::FUND_ACCOUNT_VALIDATION)
        {
            if ((isset($input[Entity::PAYMENT_METHOD]) === true) and
                ($input[Entity::PAYMENT_METHOD] === FundAccount\Validation\FundAccountType::BANK_ACCOUNT) and
                (empty($input[Entity::PERCENT_RATE]) === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Percentage rate pricing is not allowed for Bank Account Validation');
            }
        }
    }

    protected function validateEditPlanRuleProcurer($input)
    {
        if (isset($input[Entity::PROCURER]) === false)
        {
           return;
        }

        if (($input[Entity::PROCURER]) === null)
        {
            return;
        }

        $validProcurers = [Procurer::MERCHANT, Procurer::RAZORPAY];

        if (in_array($input[Entity::PROCURER], $validProcurers) === true)
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException(
            'Invalid Procurer Value');

    }

    protected function validateEditPlanRuleChannel($input)
    {
        if (array_key_exists(Entity::CHANNEL, $input) === false)
        {
            return;
        }

        if ($this->entity->isPayoutsFilterFreePayout() == false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Channel update only allowed for free payout rules');
        }

        // This is a valid scenario for free payout
        if (empty($input[Entity::CHANNEL]) === true)
        {
            return;
        }

        // We can add another validation here about when channel should be sent
        // like product checks
        self::validateChannel(Entity::CHANNEL, $input[Entity::CHANNEL]);
    }


    protected function validateAddPlanRuleEmandateOrNach($input)
    {
        if ((isset($input[Entity::PAYMENT_METHOD]) === true) and
            (
                ($input[Entity::PAYMENT_METHOD] === Payment\Method::EMANDATE) or
                ($input[Entity::PAYMENT_METHOD] === Payment\Method::NACH)
            ))
        {
            if (empty($input[Entity::PERCENT_RATE]) === false)
            {
                if ($this->isPercentagePricingAllowedForEmandateOrNach($input))
                {
                    // for recurring feature of type auto, eMandate with pricing plan should be allowed
                    app('trace')->info(
                        TraceCode::EMANDATE_PERCENTAGE_PRICING_PLAN,
                        [
                            'input'         => $input,
                        ]);
                }
                else
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Percentage rate pricing is not allowed for ' . $input[Entity::PAYMENT_METHOD]);
                }
            }

            if (isset($input[Entity::PAYMENT_METHOD_TYPE]) === true)
            {
                Payment\AuthType::validateAuthType($input[Entity::PAYMENT_METHOD_TYPE], $input[Entity::PAYMENT_METHOD]);
            }

            if (isset($input[Entity::PAYMENT_ISSUER]) === true)
            {
                Payment\RecurringType::validateRecurringType($input[Entity::PAYMENT_ISSUER]);
            }
        }
    }

    protected function isPercentagePricingAllowedForEmandateOrNach($input): bool
    {
        return ((empty($input[Entity::PAYMENT_ISSUER]) === false) and
                ($input[Entity::PAYMENT_ISSUER] === Payment\RecurringType::AUTO) and
                (empty($input[Entity::PAYMENT_METHOD]) === false) and
                ($input[Entity::PAYMENT_METHOD] === Payment\Method::EMANDATE) and
                (empty($input[Entity::PAYMENT_METHOD_TYPE]) === false) and
                ($input[Entity::PAYMENT_METHOD_TYPE] !== Payment\AuthType::AADHAAR));
    }

    protected function validateAddPlanRuleNB($input)
    {
        // Check that payment_method_type is not defined when mode is net-banking
        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::NETBANKING)
        {
            // Allowing payment_method_type to be set when refund is the feature and method is netbanking
            if ($input[Entity::FEATURE] === Feature::REFUND)
            {
                $this->validateRefundMode($input);
            }
            else
            {
                $fields = array(
                    Entity::PAYMENT_METHOD_TYPE,
                    Entity::PAYMENT_ISSUER);

                foreach ($fields as $field)
                {
                    if (isset($input[$field]) and
                        $input[$field] !== null)
                    {
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_PRICING_FIELD_NOT_REQUIRED_FOR_NB,
                            $field);
                    }
                }
            }
        }
    }

    protected function validateaddPlanRuleCard($input)
    {
        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::CARD)
        {
            if ($input[Entity::FEATURE] === Feature::REFUND)
            {
                $this->validateRefundMode($input);
            }
            else
            {
                if (isset($input[Entity::PAYMENT_METHOD_TYPE]) === true)
                {
                    $cardType = $input[Entity::PAYMENT_METHOD_TYPE];

                    $validCardTypes = [
                        CardType::DEBIT,
                        CardType::CREDIT,
                        CardType::PREPAID,
                    ];

                    if (in_array($cardType, $validCardTypes, true) === false)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'Payment method type for card should be debit / credit / prepaid');
                    }
                }

                if (isset($input[Entity::PAYMENT_METHOD_SUBTYPE]) === true)
                {
                    if ($input[Entity::FEATURE] === Feature::TOKEN_HQ)
                    {
                        $this->validateTokenHqMethodSubType($input);
                    }
                    else {
                        $subType = $input[Entity::PAYMENT_METHOD_SUBTYPE];

                        SubType::checkSubType($subType);
                    }
                }
            }
        }

        if(isset($input[Entity::PAYMENT_METHOD])===true and $input[Entity::PAYMENT_METHOD] === Payment\Method::OFFLINE
            and  (isset($input[Entity::PAYMENT_METHOD_TYPE]) === true or isset($input[Entity::PAYMENT_METHOD_SUBTYPE]) === true))
        {
            $this->throwExtraFieldsException([Entity::PAYMENT_METHOD_SUBTYPE,Entity::PAYMENT_METHOD_TYPE]);
        }
    }

    protected function validateaddPlanRuleEmi($input)
    {
        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::EMI)
        {
            if (isset($input[Entity::PAYMENT_METHOD_TYPE]) === true)
            {
                $cardType = $input[Entity::PAYMENT_METHOD_TYPE];

                $validCardTypes = [
                    CardType::DEBIT,
                    CardType::CREDIT,
                ];

                if (in_array($cardType, $validCardTypes, true) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Payment method type for card should be debit / credit');
                }
            }
        }
    }

    protected function validateAddPlanRuleBankTransfer($input)
    {
        // Bank Transfer payments can't be rejected, so
        // a percent rate rule is always required for the
        // lowest amounts, since a flat pricing would fail
        if ($input[Entity::PAYMENT_METHOD] !== Payment\Method::BANK_TRANSFER)
        {
            return;
        }

        // If it's an amount range rule, percentage rate is not mandated,
        // since the min amount may be high enough to not need it
        if ((isset($input[Entity::AMOUNT_RANGE_ACTIVE]) === true) and
            (empty($input[Entity::AMOUNT_RANGE_MIN]) === false) and
            ($input[Entity::AMOUNT_RANGE_MIN] !== 0))
        {
            return;
        }

        if ((empty($input[Entity::PERCENT_RATE]) === true) or
            (empty($input[Entity::MAX_FEE]) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Bank transfer pricing should include percent rate and max fee');
        }
    }

    protected function validateAddPlanRulePayoutFundTransfer($input)
    {
        if (($input[Entity::FEATURE] === Pricing\Feature::PAYOUT) and
            ($input[Entity::PAYMENT_METHOD] === Payout\Method::FUND_TRANSFER))
        {
            if (isset($input[Entity::PAYMENT_METHOD_TYPE]) === false)
            {
                return;
            }

            $mode = $input[Entity::PAYMENT_METHOD_TYPE];

            $validModes = [
                FundTransfer\Mode::NEFT,
                FundTransfer\Mode::IMPS,
                FundTransfer\Mode::RTGS,
                FundTransfer\Mode::IFT,
                FundTransfer\Mode::CARD,
                FundTransfer\Mode::AMAZONPAY,
            ];

            if (in_array($mode, $validModes, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payout mode should be NEFT/IMPS/RTGS/IFT/card/amazonpay',
                    'mode',
                    [
                        'mode'  => $mode,
                        'input' => $input,
                    ]);
            }
        }
    }

    protected function validateAddPlanRulePaymentNetwork($input)
    {
        if ((isset($input[Entity::PAYMENT_NETWORK]) === false) or
            ($input[Entity::PAYMENT_NETWORK] === null))
        {
            return;
        }

        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::WALLET)
        {
            if (Wallet::exists($input[Entity::PAYMENT_NETWORK]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment network for wallet should be a valid wallet name');
            }
        }

        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::CARD)
        {
            $network = $input[Entity::PAYMENT_NETWORK];

            if (Network::isValidNetwork($network) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment network for card should be a valid card name');
            }

            if (Network::isUnsupportedNetwork($network) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'This card payment network is not supported');
            }
        }

        if (($input[Entity::PAYMENT_METHOD] === Payment\Method::NETBANKING) or
            ($input[Entity::PAYMENT_METHOD] === Payment\Method::EMANDATE))
        {
            if (IFSC::exists($input[Entity::PAYMENT_NETWORK]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment network for bank/emandate should be a valid bank name');
            }
        }

        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::APP)
        {
            if (AppMethod::isValidApp($input[Entity::PAYMENT_NETWORK]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provider selected for app should be valid');
            }
        }

        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::INTL_BANK_TRANSFER)
        {
            if (IntlBankTransferMethod::isValidIntlBankTransferMode($input[Entity::PAYMENT_NETWORK]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Mode selected for Intl Bank Transfer should be valid');
            }
        }

        if ($input[Entity::TYPE] === Type::BUY_PRICING and isset($input[Entity::PAYMENT_NETWORK]))
        {
            if($input[Entity::PAYMENT_NETWORK] === Network::UNKNOWN){
                throw new Exception\BadRequestValidationFailureException(
                    'Payment Network sent is wrong. Please check the case ' .
                    '(lower/upper) of the payment network you are sending. If you are sending UNKNOWN explicitly then its not a valid network'
                );
            }

            if (in_array($input[Entity::PAYMENT_METHOD],
                [
                    Payment\Method::UPI,
                    Payment\Method::EMI,
                    Payment\Method::NACH,
                    Payment\Method::PAYLATER,
                    Payment\Method::CARDLESS_EMI,
                ]) === false)
            {
                return;
            }

            if (BuyPricing::isValidBuyPricingNetwork($input[Entity::PAYMENT_METHOD], $input[Entity::PAYMENT_NETWORK] ) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Network selected for method ' . $input[Entity::PAYMENT_METHOD] .' is invalid');
            }
        }

        if(isset($input[Entity::PAYMENT_METHOD]) === true and $input[Entity::PAYMENT_METHOD] === Payment\Method::OFFLINE
            and  isset($input[Entity::PAYMENT_NETWORK]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Offline method cannot have a network associated with it');
        }
    }

    protected function validateAddPlanRuleRefund($input)
    {
        if ($input[Entity::FEATURE] === Feature::REFUND)
        {
            if (empty($input[Entity::PERCENT_RATE]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Percentage rate pricing is not allowed for ' . $input[Entity::FEATURE]);
            }

            $this->validateRefundMode($input);
        }
    }

    protected function validateAddPlanRuleRate($input)
    {
        //
        // At least one of percent_rate and fixed_rate has to be specified
        //
        if ((isset($input[Entity::PERCENT_RATE]) === false) and
            (isset($input[Entity::FIXED_RATE]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_RATE_NOT_DEFINED);
        }
    }

    protected function validateAddPlanRuleInternational($input)
    {
        if ((isset($input[Entity::INTERNATIONAL]) === false) or
            ($input[Entity::INTERNATIONAL] === '0'))
        {
            return;
        }

        $isBuyPricingRule = (isset($input[Entity::TYPE]) and $input[Entity::TYPE] === Type::BUY_PRICING);

        $attrs = array(
            Entity::PAYMENT_NETWORK,
            Entity::PAYMENT_ISSUER,
            Entity::PAYMENT_METHOD_TYPE,
        );

        foreach ($attrs as $attr)
        {
            if ((isset($input[$attr])) and ($input[$attr] !== null) and !$isBuyPricingRule)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "For international pricing rule, attribute $attr should not be set");
            }
        }

        if ($input[Entity::PAYMENT_METHOD] !== Payment\Method::CARD)
        {
            throw new Exception\BadRequestValidationFailureException(
                'International pricing rule is only allowed for card method');
        }
    }

    protected function validateAddPlanRuleAmountRange($input)
    {
        if ($input[Entity::TYPE] === Type::BUY_PRICING)
        {
            return;
        }

        if ((isset($input[Entity::AMOUNT_RANGE_ACTIVE]) === false) or
            ($input[Entity::AMOUNT_RANGE_ACTIVE] === '0'))
        {
            return;
        }

        if ((isset($input[Entity::AMOUNT_RANGE_MIN]) === false) or
            (isset($input[Entity::AMOUNT_RANGE_MAX]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount Range Rules require both min and max end of ranges');
        }

        if ($input[Entity::AMOUNT_RANGE_MIN] >= $input[Entity::AMOUNT_RANGE_MAX])
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount Range Rules require max end of ranges to be greater than '.
                'min end of range');
        }
    }

    protected function validateAddPlanRuleMinAndMaxFee($input)
    {
        if (isset($input[Entity::MAX_FEE]) and
            ($input[Entity::MIN_FEE] > $input[Entity::MAX_FEE]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Min fee chargeable for a rule needs to be greater than Max fee');
        }
    }

    public function createPlanValidate($input)
    {
        $this->validatePlanName($input);

        unset($input[Entity::PLAN_NAME]);

        $this->validateInput('addPlanRule', $input);
    }

    public function addPlanRuleValidate($input, Plan $plan)
    {
        $this->validateInput('addPlanRule', $input);
        // The plan should already have at least one rule
        if ($plan->count() === 0)
        {
            throw new Exception\LogicException(
                'No plan rule exists for the defined plan. Blasphemy!');
        }

        $rule = $plan->first();

        // The input and pricing rule (any) gateway should match
        $this->matchGateway($rule, $input);
    }

    protected function matchGateway($planRule, $input)
    {
        $gateway = $planRule->getGateway();

        if ($gateway !== null)
        {
            if ((isset($input[Entity::GATEWAY]) === false) or
                ($input[Entity::GATEWAY] !== $gateway))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PRICING_GATEWAY_REQUIRED);
            }
        }
    }

    /**
     * Throw error if pricing plan has rules of multiple types
     *
     * @param Plan $plan
     *
     * @throws Exception\BadRequestException
     */
    public function validateTypeMatch(Plan $plan)
    {
        $newRule = $this->entity;

        $types = $plan->pluck(Entity::TYPE);
        $types = $types->push($newRule[Entity::TYPE])->unique();

        if ($types->count() > 1)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES,
                Entity::TYPE,
                $types->values()->all()
            );
        }
    }


    /**
     * When adding new rule to an existing plan - this function validates that fee_bearer
     * attribute of the new rule does not conflict with fee_bearer attributes of all merchants
     * on this pricing plan
     *
     * Conflict happens when rule is fee_bearer 'platform' but one or more merchant on this
     * pricing plan is is fee_bearer 'customer'(or vice versa)
     * @param Plan $plan
     * @param Entity $rule
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateRuleForFeeBearer(Plan $plan, Pricing\Entity $rule)
    {
        $planAssociatedMerchantFeeBearers = (new Merchant\Repository())->fetchFeeBearersForPlanId($rule->getPlanId());

        foreach($planAssociatedMerchantFeeBearers as $feeBearer)
        {
            if ($feeBearer == FeeBearer::DYNAMIC)
            {
                continue;
            }

            if ($feeBearer != $rule->getFeeBearer())
            {
                $data = [
                    'pricing_plan_id'       => $rule->getPlanId(),
                    'pricing_plan_name'     => $rule->getPlanName(),
                    'rule_fee_bearer'       => $rule->getFeeBearer(),
                ];

                $message = 'Unable to add rule to plan ' . $rule->getPlanName() . '. Rule has fee_bearer ' . $rule->getFeeBearer() .
                    '. A merchant on this plan has fee_bearer ' . $feeBearer;

                throw new Exception\BadRequestValidationFailureException(
                    $message,
                    'fee_bearer',
                    $data);
            }

        }
    }

    /**
     * Throw an error if commission plan is posted for non-rzp orgs
     *
     * @throws Exception\BadRequestException
     */
    public function validatePlanTypeForOrg()
    {
        $rule  = $this->entity;
        $type  = $rule->getType();
        $orgId = $rule->getAttribute(Entity::ORG_ID);

        if (($type === Type::COMMISSION) and ($orgId !== Org::RAZORPAY_ORG_ID))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_TYPE_COMMISSION_INVALID_FOR_NON_RZP_ORG,
                Entity::TYPE,
                [
                    'org_id' => $orgId,
                ]
            );
        }

        if (($type === Type::BUY_PRICING) and ($orgId !== Org::RAZORPAY_ORG_ID))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_TYPE_BUY_PRICING_INVALID_FOR_NON_RZP_ORG,
                Entity::TYPE,
                [
                    'org_id' => $orgId,
                ]
            );
        }
    }

    /**
     * Check whether this new rule already exists
     *
     * @param Plan $plan
     *
     * @throws Exception\BadRequestException
     */
    public function validateRuleDoesNotMatch(Plan $plan)
    {
        $rules = $plan->toArray();

        /** @var Entity $newRule */
        $newRule = $this->entity;

        foreach ($rules as $rule)
        {
            if (($rule[Entity::PRODUCT] === $newRule[Entity::PRODUCT]) and
                ($rule[Entity::PROCURER] === $newRule[Entity::PROCURER]) and
                ($rule[Entity::PAYMENT_METHOD] === $newRule[Entity::PAYMENT_METHOD]) and
                ($rule[Entity::PAYMENT_METHOD_TYPE] === $newRule[Entity::PAYMENT_METHOD_TYPE]) and
                ($rule[Entity::PAYMENT_METHOD_SUBTYPE] === $newRule[Entity::PAYMENT_METHOD_SUBTYPE]) and
                ($rule[Entity::PAYMENT_NETWORK] === $newRule[Entity::PAYMENT_NETWORK]) and
                ($rule[Entity::PAYMENT_ISSUER] === $newRule[Entity::PAYMENT_ISSUER]) and
                ($rule[Entity::INTERNATIONAL] === $newRule[Entity::INTERNATIONAL]) and
                ($rule[Entity::AMOUNT_RANGE_ACTIVE] === $newRule[Entity::AMOUNT_RANGE_ACTIVE]) and
                ($rule[Entity::AMOUNT_RANGE_MIN] === $newRule[Entity::AMOUNT_RANGE_MIN]) and
                ($rule[Entity::AMOUNT_RANGE_MAX] === $newRule[Entity::AMOUNT_RANGE_MAX]) and
                ($rule[Entity::FEATURE] === $newRule[Entity::FEATURE]) and
                ($rule[Entity::EMI_DURATION] === $newRule[Entity::EMI_DURATION]) and
                ($rule[Entity::RECEIVER_TYPE] === $newRule[Entity::RECEIVER_TYPE]) and
                ($this->isAccountTypeAndChannelSameForBothRules($rule, $newRule) === true) and
                ($rule[Entity::AUTH_TYPE] === $newRule[Entity::AUTH_TYPE]) and
                ($rule[Entity::PAYOUTS_FILTER] === $newRule[Entity::PAYOUTS_FILTER]) and
                ($rule[Entity::APP_NAME] === $newRule[Entity::APP_NAME]))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED);
            }

            if (($rule[Entity::PRODUCT] === $newRule[Entity::PRODUCT]) and
                ($rule[Entity::PROCURER] === $newRule[Entity::PROCURER]) and
                ($rule[Entity::PAYMENT_METHOD] === $newRule[Entity::PAYMENT_METHOD]) and
                ($rule[Entity::PAYMENT_METHOD_TYPE] === $newRule[Entity::PAYMENT_METHOD_TYPE]) and
                ($rule[Entity::PAYMENT_METHOD_SUBTYPE] === $newRule[Entity::PAYMENT_METHOD_SUBTYPE]) and
                ($rule[Entity::PAYMENT_NETWORK] === $newRule[Entity::PAYMENT_NETWORK]) and
                ($rule[Entity::PAYMENT_ISSUER] === $newRule[Entity::PAYMENT_ISSUER]) and
                ($rule[Entity::INTERNATIONAL] === $newRule[Entity::INTERNATIONAL]) and
                ($rule[Entity::FEATURE] === $newRule[Entity::FEATURE]) and
                ($rule[Entity::EMI_DURATION] === $newRule[Entity::EMI_DURATION]) and
                ($rule[Entity::RECEIVER_TYPE] === $newRule[Entity::RECEIVER_TYPE]) and
                ($this->isAccountTypeAndChannelSameForBothRules($rule, $newRule) === true) and
                ($rule[Entity::AUTH_TYPE] === $newRule[Entity::AUTH_TYPE]) and
                ($rule[Entity::PAYOUTS_FILTER] === $newRule[Entity::PAYOUTS_FILTER]) and
                ($rule[Entity::APP_NAME] === $newRule[Entity::APP_NAME]) and
                (isset($newRule[Entity::AMOUNT_RANGE_ACTIVE]) === true) and
                (isset($rule[Entity::AMOUNT_RANGE_ACTIVE]) === true))
            {
                $this->checkPricingRuleForAmountRangeOverlap($rule, $newRule);
            }

            if (($rule[Entity::PRODUCT] === $newRule[Entity::PRODUCT]) and
                ($rule[Entity::PROCURER] === $newRule[Entity::PROCURER]) and
                ($rule[Entity::PAYMENT_METHOD] === $newRule[Entity::PAYMENT_METHOD]) and
                ($rule[Entity::PAYMENT_METHOD_TYPE] === $newRule[Entity::PAYMENT_METHOD_TYPE]) and
                ($rule[Entity::PAYMENT_METHOD_SUBTYPE] === $newRule[Entity::PAYMENT_METHOD_SUBTYPE]) and
                ($rule[Entity::PAYMENT_NETWORK] === $newRule[Entity::PAYMENT_NETWORK]) and
                ($rule[Entity::PAYMENT_ISSUER] === $newRule[Entity::PAYMENT_ISSUER]) and
                ($rule[Entity::INTERNATIONAL] === $newRule[Entity::INTERNATIONAL]) and
                ($rule[Entity::FEATURE] === $newRule[Entity::FEATURE]) and
                ($rule[Entity::EMI_DURATION] === $newRule[Entity::EMI_DURATION]) and
                ($rule[Entity::RECEIVER_TYPE] === $newRule[Entity::RECEIVER_TYPE]) and
                ($this->isAccountTypeAndChannelSameForBothRules($rule, $newRule) === true) and
                ($rule[Entity::AUTH_TYPE] === $newRule[Entity::AUTH_TYPE]) and
                ($rule[Entity::PAYOUTS_FILTER] === $newRule[Entity::PAYOUTS_FILTER]) and
                ($rule[Entity::APP_NAME] === $newRule[Entity::APP_NAME]) and
                (empty($newRule[Entity::AMOUNT_RANGE_ACTIVE]) !== empty($rule[Entity::AMOUNT_RANGE_ACTIVE])))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PRICING_RULE_FOR_AMOUNT_RANGE_OVERLAP);
            }
        }
    }

    protected function checkPricingRuleForAmountRangeOverlap($rule, $newRule)
    {
        list($newRuleMin, $newRuleMax) = $newRule->getAmountRange();
        list($oldRuleMin, $oldRuleMax) = (new Entity($rule))->getAmountRange();

        //
        // We need to effectively check that the new pricing range does not overlap
        // with the existing pricing range.
        //
        // First we check that new range boundaries are not in between the old
        // range boundaries in any way.
        // This checks for all conditions except one.
        //
        // The old range should not be a subset of the new range and so we
        // also check for that.
        //
        // Max of one rule can be equal to min of another rule, and vice versa.
        // But min of one rule cannot be equal to min of another
        // and same for max. This needs to be ensure within the checks we have.
        //

        $flag = false;

        if (($this->between($newRuleMin, $oldRuleMin, $oldRuleMax)) or
            ($this->between($newRuleMax, $oldRuleMin, $oldRuleMax)) or
            ($newRuleMin === $oldRuleMin) or
            ($newRuleMax === $oldRuleMax))
        {
            $flag = true;
        }

        if (($this->between($oldRuleMin, $newRuleMin, $newRuleMax)) and
            ($this->between($oldRuleMax, $newRuleMin, $newRuleMax)))
        {
            $flag = true;
        }

        if ($flag)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PRICING_RULE_FOR_AMOUNT_RANGE_OVERLAP);
        }
    }

    public function validatePlanCountZero(Plan $plan)
    {
        if ($plan->count() > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_PLAN_WITH_SAME_NAME_EXISTS);
        }
    }

    public function validBuyPricingRules($rules)
    {
        $requiredKeys = [
            Pricing\Entity::AMOUNT_RANGE_MIN,
            Pricing\Entity::AMOUNT_RANGE_MAX,
            Pricing\Entity::AMOUNT_RANGE_ACTIVE,
            Pricing\Entity::FIXED_RATE,
            Pricing\Entity::PERCENT_RATE
        ];

        $requiredKeys = array_merge($requiredKeys, Pricing\Entity::$buyPricingMethods);

        array_walk($rules, function (&$value) use ($requiredKeys)
        {
            $value = array_intersect_key($value, array_flip($requiredKeys));
        });

        // Validating plan before assigning to terminal.
        $this->validateBuyPricingRules($rules);
    }

    public function validateBuyPricingRules($inputRules)
    {
        foreach ($inputRules as $rule)
        {
            if (isset($rule[Entity::TYPE]) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'type is not required for buy pricing');
            }
        }

        $inputRules = (new Entity())->groupBuyPricingRules($inputRules);

        $inputRules->map(function ($groupedRules)
        {
            $this->validateGroupedRulesRange($groupedRules->toArray());
        });
    }

    protected function validateGroupedRulesRange($groupedRules)
    {
        $size = sizeof($groupedRules);

        array_walk($groupedRules, function ($rules)
        {
            $validParams = [Entity::AMOUNT_RANGE_MIN, Entity::AMOUNT_RANGE_MAX, Entity::AMOUNT_RANGE_ACTIVE, Entity::FIXED_RATE,
                Entity::PERCENT_RATE, Entity::MAX_FEE, Entity::MIN_FEE];

            $validParams = array_merge(Entity::$buyPricingMethods, $validParams);

            $invalidParams = array_diff(array_keys($rules), $validParams);

            if (count($invalidParams) > 0)
            {
                throw new Exception\BadRequestValidationFailureException(
                    implode(', ', $invalidParams).' are not permitted');
            }
        });

        array_multisort(array_column($groupedRules, Entity::AMOUNT_RANGE_MIN), SORT_ASC, $groupedRules);

        $expectedPrice = 0;

        for ($i=0; $i<$size; $i++)
        {
           if ((int)$groupedRules[$i][Entity::AMOUNT_RANGE_MIN] !== (int)$expectedPrice)
           {
               throw new Exception\BadRequestValidationFailureException(
                   ErrorCode::BAD_REQUEST_BUY_PRICING_RANGE_VALIDATION_FAILED);
           }

            $expectedPrice = (int)($groupedRules[$i][Entity::AMOUNT_RANGE_MAX] ?? 0);
        }

        if (isset($expectedPrice) and $expectedPrice !== 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_BUY_PRICING_RANGE_VALIDATION_FAILED);
        }
    }

    public function validateProduct($attribute, $value)
    {
        Product::validate($value);
    }

    public function validateType($attribute, $value)
    {
        Type::validate($value);
    }

    protected function between($n, $min, $max)
    {
        return (($min < $n) and ($n < $max));
    }

    protected function validateChannel($attribute, $value)
    {
        // Only direct channels can have this set for now
        BASChannel::validate($value);
    }

    protected function validateAccountType($attribute, $value)
    {
        // Only direct channels can have this set for now
        AccountType::exists($value);
    }

    protected function validateTokenHqMethodSubType($input)
    {
        if (isset($input[Entity::PAYMENT_METHOD_SUBTYPE]) === true)
        {
            $TokenType = $input[Entity::PAYMENT_METHOD_SUBTYPE];

            $validTokenTypes = [
                TokenPricing::REQUEST_TOKEN_CREATE,
                TokenPricing::REQUEST_CRYPTOGRAM,
                TokenPricing::REQUEST_PAR,
                TokenPricing::SUCCESS_TOKENISED_PAYMENT,
                TokenPricing::SUCCESS_TOKEN_CREATE,
                TokenPricing::SAAS_FEE,
            ];

            if (in_array($TokenType, $validTokenTypes, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException('Not a valid sub_type: ' . $subtype);
            }
        }
    }

    protected function validateRefundMode($input)
    {
        if ($input[Entity::FEATURE] === Feature::REFUND)
        {
            if (isset($input[Entity::PAYMENT_METHOD_TYPE]) === true)
            {
                $mode = $input[Entity::PAYMENT_METHOD_TYPE];

                $method = $input[Entity::PAYMENT_METHOD];

                $validModes = [
                    FundTransfer\Mode::NEFT,
                    FundTransfer\Mode::IMPS,
                    FundTransfer\Mode::RTGS,
                    FundTransfer\Mode::UPI,
                    FundTransfer\Mode::IFT,
                    FundTransfer\Mode::CT,
                ];

                switch ($method)
                {
                    case Payment\Method::CARD :
                        $validModes = [
                            FundTransfer\Mode::UPI,
                            FundTransfer\Mode::NEFT,
                            FundTransfer\Mode::IMPS,
                            FundTransfer\Mode::CT,
                        ];

                        break;

                    case Payment\Method::UPI :
                        $validModes = [
                            FundTransfer\Mode::UPI,
                        ];

                        break;

                    case Payment\Method::NETBANKING:
                        $validModes = [
                            FundTransfer\Mode::NEFT,
                            FundTransfer\Mode::IMPS,
                            FundTransfer\Mode::RTGS,
                            FundTransfer\Mode::IFT,
                        ];

                        break;
                }

                if (in_array($mode, $validModes, true) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Refund mode should be ' . implode('/', $validModes),
                        'mode',
                        [
                            'mode'  => $mode,
                            'input' => $input,
                        ]
                    );
                }
            }
        }
    }

    protected function isAccountTypeAndChannelSameForBothRules(array $rule, Pricing\Entity $newRule) : bool
    {
        if (($this->isAccountTypeSameForBothRules($rule, $newRule) === true) and
            ($this->isChannelSameForBothRules($rule, $newRule) === true))
        {
            return true;
        }

        return false;
    }

    protected function isAccountTypeSameForBothRules(array $rule, Pricing\Entity $newRule) : bool
    {
        if ((isset($rule[Entity::ACCOUNT_TYPE]) === true) and
            (isset($newRule[Entity::ACCOUNT_TYPE]) === true) and
            ($rule[Entity::ACCOUNT_TYPE] !== $newRule[Entity::ACCOUNT_TYPE]))
        {
            return false;
        }

        return true;
    }

    protected function isChannelSameForBothRules(array $rule, Pricing\Entity $newRule) : bool
    {
        if ((isset($rule[Entity::CHANNEL]) === true) and
            (isset($newRule[Entity::CHANNEL]) === true) and
            ($rule[Entity::CHANNEL] !== $newRule[Entity::CHANNEL]))
        {
            return false;
        }

        return true;
    }

    /*
    This is to validate that auth_type field in pricing rule with the following conditions-
    1. The auth_type field is not compulsory.
    2. If product is banking and auth_type is set, auth_type must be either proxy or private.
    3. If payment_method_type is debit and auth_type is set, auth_type must be of type pin.
    */
    protected function validateAddPlanRuleAuthType($input)
    {
        // This ensures if auth_type is not set we allow the rule creation to happen.
        if (empty($input[Entity::AUTH_TYPE]) === true)
        {
            return;
        }

        // This ensures that apart from product banking or payment method type is debit, auth_type is not sent in any
        // other condition.
        if($this->isAuthTypeSetOnlyForProductBankingOrPaymentMethodTypeDebit($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The auth type field can be sent only when payment method type is debit or product is banking');
        }

        // Check if Auth type is valid for product as banking.
        if ($this->isAuthTypeValidForProductBanking($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The selected auth type is invalid');
        }

        // Check if Auth type is valid for payment_method_type as debit.
        if ($this->isAuthTypeValidForPaymentMethodTypeDebit($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The selected auth type is invalid');
        }

        //Check if Auth type is not being sent when payment method is offline
        if(isset($input[Entity::PAYMENT_METHOD])===true and $input[Entity::PAYMENT_METHOD] === Payment\Method::OFFLINE
            and  isset($input[Entity::AUTH_TYPE]) === true)
        {
            $this->throwExtraFieldsException(Entity::AUTH_TYPE);
        }
    }

    protected function isProductBanking($input) : bool
    {
        if ((isset($input[Entity::PRODUCT]) === true) and
            ($input[Entity::PRODUCT] === Product::BANKING))
        {
            return true;
        }

        return false;
    }

    protected function isAuthTypeSetOnlyForProductBankingOrPaymentMethodTypeDebit($input) : bool
    {
        $isProductBanking = $this->isProductBanking($input);

        $isPaymentMethodTypeDebit = $this->isPaymentMethodTypeDebit($input);

        if ((empty($input[Entity::AUTH_TYPE]) === false) and
            ($isProductBanking === false) and
            ($isPaymentMethodTypeDebit === false))
        {
            return false;
        }

        return true;
    }

    protected function isPaymentMethodTypeDebit($input)
    {
        if ((isset($input[Entity::PAYMENT_METHOD_TYPE]) === true) and
            ($input[Entity::PAYMENT_METHOD_TYPE] === CardType::DEBIT))
        {
            return true;
        }

        return false;
    }

    protected function isAuthTypeValidForPaymentMethodTypeDebit($input) : bool
    {

        if ($this->isPaymentMethodTypeDebit($input) === true)
        {
            if ($input[Entity::AUTH_TYPE] !== Payment\AuthType::PIN)
            {
                return false;
            }
        }

        return true;
    }

    protected function isAuthTypeValidForProductBanking($input) : bool
    {
        if ($this->isProductBanking($input) === true)
        {
            if (in_array($input[Entity::AUTH_TYPE],
                         self::ALLOWED_AUTH_TYPE_FOR_BANKING_PRODUCT) === false)
            {
                return false;
            }
        }

        return true;
    }
}
