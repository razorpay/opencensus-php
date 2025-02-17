<?php

namespace RZP\Models\Pricing\Calculator;

use App;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Exception\LogicException;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card;
use RZP\Models\Currency\Core;
use RZP\Models\PaymentsUpi;
use RZP\Models\Currency\Currency;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Pricing;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as QrV2Entity;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Pricing\Fee;
use RZP\Models\Base as BaseModel;
use RZP\Models\Order\ProductType;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment as PaymentModel;
use RZP\Constants\Entity ;
use RZP\Models\Merchant;
use RZP\Models\Feature;


// Terminal Calculator extends Payment Calculator.
// Take extra care while modifying existing logic.
class Payment extends Base
{
    const FLEXMONEY      = 'flexmoney';
    const OFFLINE        = 'offline';
    const HCIN_IFSC      = 'HCIN';
    const KRBE_IFSC      = 'KRBE';
    const CSHE_IFSC      = 'CSHE';
    const TVSC_IFSC      = 'TVSC';
    const MERCHANT_PROCURER_MIDS = ['GtFwVSbNTDTM9C', 'GtG3WLjGVjzx2n', 'CgtLpPjpmyg9ct', 'ELi8nocD30pFkb', 'G09v5FON5fsU9v', 'FlZgyQ2HD6eIET'];

    protected $fallbackStandardPlanExperimentId   = null;

    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct($entity, $product);

        $app = App::getFacadeRoot();

        $this->fallbackStandardPlanExperimentId = $app['config']->get('app.pricing_fallback_standard_plan_experiment_id');

    }

    protected static $flexMoneyIssuers = [
          IFSC::BARB,
          IFSC::HDFC,
          IFSC::KKBK,
          IFSC::FDRL,
          IFSC::IDFB,
          IFSC::ICIC,
          self::HCIN_IFSC,
          self::KRBE_IFSC,
          self::CSHE_IFSC,
          self::TVSC_IFSC,
    ];

    protected function getBasicPricingRule(Pricing\Plan $pricing, $feature)
    {
        $method   = $this->entity->getMethod();
        $orgId    = $this->entity->merchant->org->getId();
        $product  = $this->product;

        $payment = $this->entity;
        $merchantId = $payment->getMerchantId();

        if ($this->isMerchantProcuredPayment() === true){

            if($this->isMerchantWhitelistedForProcurerPricing($merchantId))
            {
                $procurer = $payment->terminal->getProcurer();

                $filters = $this->getBasicPricingRuleFiltersForProcuredPayment($product, $feature, $procurer, $method);
            }
            else
            {
                $filters = $this->getBasicPricingRuleFilters($product, $feature, $method);
            }
        }
        else
        {
            $filters = $this->getBasicPricingRuleFilters($product, $feature, $method);
        }

        $rules = $this->applyFiltersOnRules($pricing, $filters);

        $rulesCount = count($rules);

        //
        // If pricing for the feature is optional, no rules may exist
        // In this case, we add the zero pricing rule and return
        //
        $isFeatureOptional = $this->isFeatureOptional($feature);

        if (($rulesCount === 0) and
            ($isFeatureOptional === true) and
            ($orgId === Org\Entity::RAZORPAY_ORG_ID))
        {
            $zeroPricingRule = (new Fee)->getZeroPricingPlanRule($this->entity);

            $this->pricingRules->push($zeroPricingRule);

            return;
        }

        $isInternalPaymentMethod = PaymentModel\Method::isInternalPaymentMethod($method);

        if (($rulesCount === 0) and ($isInternalPaymentMethod === true) and ($orgId === Org\Entity::RAZORPAY_ORG_ID))
        {
            $zeroPricingRule = (new Fee)->getZeroPricingPlanRule($this->entity);

            $this->pricingRules->push($zeroPricingRule);

            return;
        }

        $rule = $this->getPricingRule($rules, $method);

        if ($rule === null)
        {
            $this->trace->count(count($pricing) == 0? Metric::SERVER_ERROR_NO_PRICING_RULE_FOUND : Metric:: SERVER_ERROR_MULTIPLE_PRICING_RULES_FOUND,
                [
                    'route_name' => $this->app['api.route']->getCurrentRouteName()
                ]);

            throw new Exception\LogicException(
                'No appropriate pricing rule found for entity ' . $this->entity->getEntity(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                ['entity' => $this->entity->toArray()]);
        }

        $this->pricingRules->push($rule);
    }


    protected function getAddOnPricingRule(Pricing\Plan $pricing, array $features, $entityName)
    {
        // this is for upi autopay pricing changes.
        // if new upi autopay pricing rule is picked, it will have subtype value that is initial/auto.
        // in that case we should not add recurring addon rule
        if (($this->entity->getEntity() === Entity::PAYMENT) and
            ($this->entity->isUpiRecurring() === true) and
            (in_array(Pricing\Feature::RECURRING, $features)) and
            ($this->pricingRules[0]->getPaymentMethodSubType() !== null))
        {
            if (($key = array_search(Pricing\Feature::RECURRING, $features)) !== false)
            {
                array_splice($features, $key, 1);
            }
        }

        $method  = $this->entity->getMethod();
        $product = $this->product;

        foreach ($features as $feature)
        {
            $filters = $this->getBasicPricingRuleFiltersForFeature($product, $feature, $method);

            $rules = $this->applyFiltersOnRules($pricing, $filters);

            if (count($rules) > 0){
                // For features where a single rule is added for all payment methods,
                // skip method level filtering if such rule found
                if (in_array($feature, Pricing\Feature::METHOD_AGNOSTIC_FEATURES)){

                    if ($rules[0]->getPaymentMethod() == null){
                        if (count($rules) > 1)
                        {
                            throw new Exception\LogicException(
                                'Only 1 pricing rule should have been present here. Found: ' . count($rules),
                                null);
                        }else{
                            $this->pricingRules->push($rules[0]);
                        }
                    }else{

                        $rule = $this->getPricingRule($rules, $method);

                        $this->pricingRules->push($rule);
                    }
                }
                else
                {
                    $rule = $this->getPricingRule($rules, $method);

                    $this->pricingRules->push($rule);
                }
            }

        }
    }

    protected function getPricingRule($rules, $method)
    {
        $rules = $this->getRelevantPricingRuleForProcurer($rules);

        $rules = $this->getRelevantPricingRuleForSourceChannel($rules);

        $rule = $this->getRelevantPricingRuleForMethod($rules, $method);

        return $rule;
    }

    protected function getRelevantPricingRulesForFeeBearer($rules)
    {
        $merchant = $this->entity->merchant;

        if ($merchant === null)
        {
            return $rules;
        }

        $feeBearer = $merchant->getFeeBearer();

        if ($feeBearer === FeeBearer::DYNAMIC)
        {
            return $rules;
        }

        $filters = [
            [Pricing\Entity::FEE_BEARER, $feeBearer, true, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }

    protected function getRelevantPricingRuleForMethod($rules, $method)
    {
        $rule = null;

        if ($method === PaymentModel\Method::CARD)
        {
            $rule = $this->getRelevantPricingRuleForCardPayment($rules);
        }
        else if ($method === PaymentModel\Method::WALLET)
        {
            $rule = $this->getRelevantPricingRuleForWalletPayment($rules);
        }
        else if ($method === PaymentModel\Method::NETBANKING)
        {
            $rule = $this->getRelevantPricingRuleForNBPayment($rules);
        }
        else if ($method === PaymentModel\Method::UPI)
        {
            $rule = $this->getRelevantPricingRuleForUPI($rules);
        }
        else if ($method === PaymentModel\Method::AEPS)
        {
            $rule = $this->getRelevantPricingRuleForAeps($rules);
        }
        else if ($method === PaymentModel\Method::EMANDATE)
        {
            $rule = $this->getRelevantPricingRuleForEmandate($rules);
        }
        else if ($method === PaymentModel\Method::EMI)
        {
            $rule = $this->getRelevantPricingRuleForEmi($rules);
        }
        else if ($method === PaymentModel\Method::BANK_TRANSFER)
        {
            $rule = $this->getRelevantPricingRuleForBankTransfer($rules);
        }
        else if ($method === PaymentModel\Method::CARDLESS_EMI)
        {
            $rule = $this->getRelevantPricingRuleForCardlessEmi($rules);
        }
        else if ($method === PaymentModel\Method::PAYLATER)
        {
            $rule = $this->getRelevantPricingRuleForPayLater($rules);
        }
        else if ($method === PaymentModel\Method::NACH)
        {
            $rule = $this->getRelevantPricingRuleForNach($rules);
        }
        else if ($method === PaymentModel\Method::APP)
        {
            $rule = $this->getRelevantPricingRuleForAPP($rules);
        }
        else if ($method === PaymentModel\Method::OFFLINE)
        {
            $rule = $this->getRelevantPricingRuleForOffline($rules);
        }
        else if ($method === PaymentModel\Method::INTL_BANK_TRANSFER)
        {
            $rule = $this->getRelevantPricingRuleForIntlBankTransfer($rules);
        }
        // else if ($method === PaymentModel\Method::TRANSFER)
        // {
        //     $rule = $this->getRelevantPricingRuleForTransfer($rules);
        // }
        else if ($method === PaymentModel\Method::GIFT_CARDS)
        {
            $rule = $this->getRelevantPricingRuleForGiftCard($rules);
        }
        else
        {
            $rule = $this->validateAndGetOnePricingRule($rules);
        }

        return $rule;
    }

    protected function getRelevantPricingRuleForProcurer($rules)
    {
        $payment = $this->entity;
        // For optimizer merchants we need to do procurer based filtering for all payments
        // as we started supporting customer fee on optimizer merchants
        if ($payment->merchant->isFeeBearerCustomerOrDynamic() === true && $payment->merchant->isFeatureEnabled(Feature\Constants::RAAS) == false)
        {
            return $rules;
        }

        //
        // Transfer method, CoD method doesn't have terminal associated
        //
        if (($payment->getMethod() === PaymentModel\Method::TRANSFER) or
            ($payment->isCoD() === true) or ($payment->getMethod() === PaymentModel\Method::INTL_BANK_TRANSFER))
        {
            return $rules;
        }

        $procurer = 'razorpay';

        $isOptimizerCFBFlow = $payment->merchant->isAtLeastOneFeatureEnabled(Feature\Constants::OPTIMIZER_CFB_FEATURES);
        // this is done to prevent null terminals entity check.
        // ref : https://razorpay.slack.com/archives/C04BDR5TEGL/p1700459478960999
        if (($payment->hasTerminal() === false) or
            ($payment->terminal === null))
        {
            if ($isOptimizerCFBFlow == false )
            {
                return $rules;
            }
            // for optimizer cfb merchants we need to do set merchant as procurer
            // even if terminal is not present but gateway is present
            if ($payment->getGateway() != null)
            {
                $procurer = "merchant";
            }
        }
        else {
            $procurer = $payment->terminal->getProcurer();
        }

        $filters = [
            [Pricing\Entity::PROCURER, $procurer, true, null]
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }

    protected function getRelevantPricingRuleForCorporateCardPayment($rules)
    {
        $payment = $this->entity;

        $cardType = $payment->card->getTypeElseDefault();

        $international = $payment->isInternational();

        $receiverType = $payment->getReceiverType();

        $authType = $payment->getAuthType();

        $network = Card\Network::getCode($payment->card->getNetwork());

        $issuer = $payment->card->getIssuer();

        $subtype = $payment->card->getSubtype();

        $filters1 = [
            [Pricing\Entity::RECEIVER_TYPE,             $receiverType,  true,   null    ],
            [Pricing\Entity::INTERNATIONAL,             $international, false,  false   ],
            [Pricing\Entity::PAYMENT_METHOD_SUBTYPE,    $subtype,       true,   null    ],
            [Pricing\Entity::PAYMENT_NETWORK,           $network,       true,   null    ],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters1);

        $filters2 = [
            [Pricing\Entity::PAYMENT_METHOD_TYPE,       $cardType,      true,   null    ],
            [Pricing\Entity::AUTH_TYPE,                 $authType,      true,   null    ],
            [Pricing\Entity::PAYMENT_ISSUER,            $issuer,        true,   null    ],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters2);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForSourceChannel($rules)
    {
        $payment = $this->entity;

        $sourceChannel = $payment->getSourceChannel();

        // Empty channel scenario will be applicable for online payments
        // Adding this rule only if source channel is present to ensure it doesn't affect online payments flow
        if ((empty($sourceChannel) === true) or
            ($sourceChannel === Pricing\SourceChannel::ONLINE))
        {
            $filters = [
                [Pricing\Entity::CHANNEL, null, false, false],
            ];

            return $this->applyFiltersOnRules($rules, $filters);
        }

        $filters = [
            [Pricing\Entity::CHANNEL, $sourceChannel, false, false],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }

    protected function getRelevantPricingRuleForCardPayment($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        // Fee based on the method type
        $payment = $this->entity;

        if ($payment->isSodexoPayment())
        {
            return $this->getRelevantPricingRuleForSodexoCardPayment($rules);
        }

        $cardType = $payment->card->getTypeElseDefault();

        $international = $payment->isInternational();

        try
        {
            if (!$international and $payment->card->isAmex() and $payment->merchant->isEnableInternationalPricingForAmexEnabled())
            {
                $isInternationalEnabled = $payment->merchant->isInternational();
                $merchantCountry =  $payment->merchant->getCountry();
                $cardCountry = $payment->card->getCountry();
                if ($isInternationalEnabled and $cardCountry !== 'IN'  and $merchantCountry === 'IN')
                {
                    $international = true;
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::AMEX_INTERNATIONAL_PRICING_ERROR,[
                'merchantId'  => $payment->merchant->getMerchantId(),
                'error' => $e->getMessage()
            ]);
            $international = false;
        }

        $receiverType = $payment->getReceiverType();

        $authType = $payment->getAuthType();

        $network = Card\Network::getCode($payment->card->getNetwork());

        $issuer = $payment->card->getIssuer();

        $subtype = $payment->card->getSubtype();

        $orgId    = $this->entity->merchant->org->getId();

        if($subtype === 'business' && $orgId === Org\Entity::RAZORPAY_ORG_ID)
        {
            return $this->getRelevantPricingRuleForCorporateCardPayment($rules);
        }

        // Current Implementation
        // * Filter based on receiver type
        // * Filter based on international
        // * Filter based on Network
        // * Filter based on Auth Type
        // * If its amex, then stop
        // * Filter based on Card Type
        // * Filter based on AmountRange
        // * Choose based on Amount

        // Structure is as follows:
        // Field name, Field value, Choose default (true/false), default value

        // The sequence should not be changed as it changes the behaviour.
        // Right now if the receiver_type is present it needs to be selected no
        // matter what otherwise default type is used
        $filters1 = [
            [Pricing\Entity::RECEIVER_TYPE,         $receiverType,  true,   null    ],
            [Pricing\Entity::INTERNATIONAL,         $international, false,  false   ],
            [Pricing\Entity::PAYMENT_NETWORK,       $network,       true,   null    ],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters1);

        if ($network === Card\Network::AMEX && !$international)
        {
            return $this->validateAndGetOnePricingRule($rules);
        }

        if ($cardType === Card\Type::PREPAID)
        {
            $filterPrepaid = [
                [Pricing\Entity::PAYMENT_METHOD_TYPE,   $cardType,      false,   null    ],
            ];

            $prepaidRules = $this->applyFiltersOnRules($rules, $filterPrepaid);

            if (empty($prepaidRules) === true)
            {
                $cardType = Card\Type::CREDIT;
            }
        }

        // If network is not amex, we can check for AMOUNT RANGE FILTERS
        $filters2 = [
            [Pricing\Entity::PAYMENT_METHOD_TYPE,       $cardType,      true,   null    ],
            [Pricing\Entity::PAYMENT_METHOD_SUBTYPE,    $subtype,       true,   null    ],
            [Pricing\Entity::AUTH_TYPE,                 $authType,      true,   null    ],
            [Pricing\Entity::PAYMENT_ISSUER,            $issuer,        true,   null    ],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters2);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function validateAndGetOnePricingRule($pricing)
    {

        /*
         * Reason this "if" block of code is needed:
         * During dynamic fee bearer rollout - fee_bearer is/was the very first filter attribute in
         * payment pricing rule filtering
         * However at initial rollout, validations were missing for some time. These validations were later added
         *  to ensure that customer rules were not added to platform
         * merchant and vice versa. But in the interim, some rules got added of the opposite fee_bearer type.
         *  Even now, when a merchant is edited, it is possible that rules of opposite
         * fee bearer may be present.
         * .
         * .
         * Later, we removed/are removing via razorx the fee_bearer filter. So on account of above, we *may*
         *  have multiple redundant rules.
         * Due to above reason, we may end up with situation where payment could fail due to 2 rules being present
         * 1 with platform fee_bearer and other with customer fee_bearer
         *
         * This if block is a last shot attempt to remove the redundant rule by applying fee_bearer filter
         */
        if (count($pricing) > 1)
        {
            $pricing = $this->getRelevantPricingRulesForFeeBearer($pricing);
        }

        return parent::validateAndGetOnePricingRule($pricing);
    }

    protected function getRelevantPricingRuleForApp($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $payment = $this->entity;

        $wallet = $payment->getWallet();

        // Current Implementation
        // * Filter based on wallet which is a provider

        // Structure is as follows:
        // Field name, Field value, Choose default (true/false), default value
        $filter = array(
            [Pricing\Entity::PAYMENT_NETWORK, $wallet, true, null]
        );

        $rules = $this->applyFiltersOnRules($rules, $filter);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForWalletPayment($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $payment = $this->entity;

        $wallet = $payment->getWallet();

        // Current Implementation
        // * Filter based on wallet

        // Structure is as follows:
        // Field name, Field value, Choose default (true/false), default value
        $filter = array(
            [Pricing\Entity::PAYMENT_NETWORK, $wallet, true, null]
        );

        $rules = $this->applyFiltersOnRules($rules, $filter);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForNBPayment($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $payment = $this->entity;

        $bank = $payment->getBank();

        // Current Implementation
        // * Filter based on AmountRange
        // * Choose based on Amount

        $filters = [
            [Pricing\Entity::PAYMENT_NETWORK, $bank, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForUPI($rules)
    {
        /** @var PaymentModel\Entity $payment */
        $payment = $this->entity;

        $order = $payment->order;

        $receiverType = $payment->getReceiverType();

        $filters = [];

        /*
         * In case of UPI PL payments, order product_type will be payment_link_v2
         * In such case, we need to fetch default Pricing for UPI (no VPA fallback pricing)
         */
        if((empty($order) === false) and ($order->getProductType() === ProductType::PAYMENT_LINK_V2))
        {
            $receiverType = null;
        }

        if ($payment->isQrV2UpiPayment()) {
            /** @var QrV2Entity $qrCode */
            $qrCode = $payment->receiver;

            // In case of QrV2 payments received on checkout we need to fetch
            // default pricing for UPI (no qr_code fallback pricing).
            if ($qrCode !== null && $qrCode->isCheckoutQrCode()) {
                $receiverType = null;
            }

            // In case of QrV2 payments received on Razorpay Pos we need to fetch
            // zero pricing for UPI (no qr_code fallback pricing).
            if (($qrCode !== null) and ($qrCode->isRazorpayPosQRCode() === true)) {
                $receiverType = self::OFFLINE;
            }
        }

        if ($payment->isCreditCardOnUpi()=== true)
        {
            if (($payment->isFeeBearerCustomer() === false) and
                ($payment->checkIfCCOnUPIPricingSplitzExperimentEnabled() === true))
            {
                $receiverType = PaymentsUpi\PayerAccountType::PRICING_PLAN_RECEIVER_TYPE_CREDIT;
            }
        }


        if ($payment->isCreditLineOnUpi()=== true)
        {
            if (($payment->isFeeBearerCustomer() === false) and
                ($payment->checkIfCreditLineOnUPIPricingSplitzExperimentEnabled() === true))
            {
                $receiverType = PaymentsUpi\PayerAccountType::PRICING_PLAN_RECEIVER_TYPE_CREDIT_LINE;
            }
        }

        /*
         *  For CC on Turbo UPI payments, separate pricing is to be applied INSTEAD of the base UPI pricing.
         *  CC on Turbo UPI pricing is identified by
         *  1. payment_method_type = in_app &
         *  2. receiver_type = credit.
         *  The filter payment_method = upi is already applied in the method getBasicPricingRuleFilters() before.
         */
        if ($payment->isInAppCreditCardOnUpi() === true)
        {
            $receiverType = PaymentsUpi\PayerAccountType::PRICING_PLAN_RECEIVER_TYPE_CREDIT;
            $filters[] = [Pricing\Entity::PAYMENT_METHOD_TYPE, Merchant\Methods\Entity::IN_APP, true, null];
        }
        /*
         * If, on the other hand, it's not a cc on turbo upi payment, we should filter out the in_app rule so that
         * there is no rule conflict at the end and the correct filter gets applied for a non cc on turbo upi payment
         */
        else
        {
            $filters[] = [Pricing\Entity::PAYMENT_METHOD_TYPE, null, false, false];
        }

        if ($payment->isPPIOnUpi()=== true)
        {
            if (($payment->isFeeBearerCustomer() === false) and
                ($payment->checkIfPPIOnUPIPricingSplitzExperimentEnabled() === true))
            {
                $receiverType = PaymentsUpi\PayerAccountType::PRICING_PLAN_RECEIVER_TYPE_WALLET;
            }
        }

        $filters[] = [Pricing\Entity::RECEIVER_TYPE, $receiverType, true, null];

        $recurringType = $payment->getRecurringType();

        if($payment->isUpiRecurring())
        {
            if($payment->getRecurringType() === PaymentModel\RecurringType::CARD_CHANGE)
            {
                $recurringType = PaymentModel\RecurringType::INITIAL;
            }

            $upiAutopayPricingVariant = $this->evaluateSplitzExperimentForUpiAutopayPricingBlacklist($payment->getMerchantId());

            if($upiAutopayPricingVariant === true)
            {
                $recurringType = null;
            }
        }

        // this is to filter upi recurring (initial/auto) or onetime upi pricing rule
        $filters[] = [Pricing\Entity::PAYMENT_METHOD_SUBTYPE, $recurringType, false, null];

        $rules = $this->applyFiltersOnRules($rules, $filters);
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    /**
     * Evaluates the Splitz experiment for UPI Autopay pricing blacklist.
     *
     * This method checks if the given merchant ID is part of the UPI Autopay pricing blacklist
     * as part of the Splitz experiment.
     *
     * @param int $merchantId The ID of the merchant to evaluate.
     * @return bool True if the variant is 'variant_on', false otherwise.
     */
    protected function evaluateSplitzExperimentForUpiAutopayPricingBlacklist($merchantId)
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.upi_autopay_pricing_blacklist'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $merchantId,
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::UPI_AUTOPAY_PRICING_BLACKLIST
            );
        }

        return false;
    }

    protected function getRelevantPricingRuleForAeps($rules)
    {
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForEmandate($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $payment = $this->entity;

        $bank = $payment->getBank();

        $authType = $payment->getGlobalOrLocalTokenEntity()->getAuthType();

        $recurringType = $payment->getRecurringType();

        // Current Implementation
        // * Filter based on AmountRange
        // * Choose based on Amount
        // * Choose based on Authentication type
        // * Choose based on Recurring type

        $filters = [
            [Pricing\Entity::PAYMENT_NETWORK,     $bank,          true, null],
            [Pricing\Entity::PAYMENT_METHOD_TYPE, $authType,      true, null],
            [Pricing\Entity::PAYMENT_ISSUER,      $recurringType, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForNach($rules)
    {
        $payment = $this->entity;

        $recurringType = $payment->getRecurringType();

        $filters = [
            [Pricing\Entity::PAYMENT_ISSUER, $recurringType, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForEmi($rules)
    {
        $payment = $this->entity;
        $emiPlan = $payment->emiPlan;
        $cardType = $payment->card->getTypeElseDefault();

        $network = Card\Network::getCode($payment->card->getNetwork());

        $emiDuration = $emiPlan->getDuration();

        $issuer = $emiPlan->getIssuer();

        $subtype = $payment->card->getSubtype();

        $orgId    = $this->entity->merchant->org->getId();

        if($subtype === 'business' && $orgId === Org\Entity::RAZORPAY_ORG_ID)
        {
            return $this->getRelevantPricingRuleForCorporateCardPayment($rules);
        }

        //Emi duration and issuer filter is for merchant subvented model
        //in normal emi it will be null where feature is payment
        $filters1 = array(
            [Pricing\Entity::PAYMENT_METHOD_SUBTYPE,    $subtype,       true,   null    ],
            [Pricing\Entity::PAYMENT_NETWORK,        $network,     true, null ],
            [Pricing\Entity::PAYMENT_ISSUER,         $issuer,      true, null ],
            [Pricing\Entity::PAYMENT_METHOD_TYPE,    $cardType,    true, null ],
            [Pricing\Entity::EMI_DURATION,           $emiDuration, true, null ],
        );

        $rules = $this->applyFiltersOnRules($rules, $filters1);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForBankTransfer($rules)
    {
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForOffline($rules)
    {
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForIntlBankTransfer($rules)
    {
        $payment = $this->entity;

        $wallet = $payment->getWallet();

        $filter = array(
            [Pricing\Entity::PAYMENT_NETWORK, $wallet, true, null]
        );

        $rules = $this->applyFiltersOnRules($rules, $filter);

        return $this->validateAndGetOnePricingRule($rules);
    }

    /**
     * @throws LogicException
     */
    protected function getRelevantPricingRuleForGiftCard($rules)
    {
        $payment = $this->entity;

        $gateway = $payment->getGateway();

        // Returns giftcard_razorpay in case of gateway wallet_razorpaywallet
        $giftCard = $this->getGiftCardBasisGateway($gateway);

        $filter = array(
            [Pricing\Entity::PAYMENT_NETWORK, $giftCard, false, null]
        );

        $rules = $this->applyFiltersOnRules($rules, $filter);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getGiftCardBasisGateway($gateway): string
    {
        switch ($gateway) {
            case "wallet_razorpaywallet" :
                return "razorpay_giftcard";
        }

        return $gateway;
    }

    protected function getRelevantPricingRuleForCardlessEmi($rules)
    {
        $payment = $this->entity;

        $provider = $payment->getWallet();

        if($this->isFlexMoneyProvider($provider)) {
            $provider = self::FLEXMONEY;
        }

        // @todo: Pricing structure to do discussed with product
        $filters = [
            [Pricing\Entity::PAYMENT_ISSUER, $provider, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForPayLater($rules)
    {
        $payment = $this->entity;

        $provider = $payment->getWallet();

        $filters = [
            [Pricing\Entity::PAYMENT_ISSUER, $provider, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->validateAndGetOnePricingRule($rules);
    }

    /**
     *
     * Ensure that all the pricing rules have the same fee_bearer value.
     * @param  $pricingRules
     * @return return the common fee_bearer value
     * @throws Exception\LogicException when pricingRules has more than 1 type of fee_bearer value
     */
    public function validateAndGetFeeBearer() : string
    {
        $pricingRules = $this->pricingRules;

        if (count($pricingRules) < 1)
        {
            throw new Exception\LogicException(
                'No pricing rule found. Expected atleast 1');
        }

        $feeBearers = [];

        foreach ($pricingRules as $rule)
        {
            array_push($feeBearers, $rule->getFeeBearer());
        }

        $feeBearersUnique = array_unique($feeBearers);

        if (count($feeBearersUnique) !== 1)
        {
            $exceptionData = [];

            foreach ($pricingRules as $rule)
            {
                $ruleData = [
                    Pricing\Entity::ID          => $rule->getId(),
                    Pricing\Entity::FEE_BEARER  => $rule->getFeeBearer(),
                ];

                array_push($exceptionData, $ruleData);
            }
            throw new Exception\LogicException(
                'Expected only one type of feebearer for all rules', null, $exceptionData);
        }

        return $pricingRules[0]->getFeeBearer();
    }

    public function validateAndGetFeeModel()
    {
        $pricingRules = $this->pricingRules;

        if (count($pricingRules) < 1)
        {
            throw new Exception\LogicException(
                'No pricing rule found. Expected atleast 1');
        }

        return $pricingRules[0]->getFeeModel();
    }

    private function getCardDetails(): ?array
    {
        if ($this->entity->getMethod() == PaymentModel\Method::CARD) {
            return [
                Card\Entity::ISSUER => $this->entity->card?->getIssuer(),
                Card\Entity::TYPE => $this->entity->card?->getTypeElseDefault(),
                Card\Entity::NETWORK => $this->entity->card?->getNetwork(),
                Card\Entity::SUBTYPE => $this->entity->card?->getSubtype()
            ];
        }

        return null;
    }

    private function getPaymentDetails(): array
    {
        return [
            PaymentModel\Entity::BANK => $this->entity->getBank(),
            PaymentModel\Entity::RECEIVER_TYPE => $this->entity->getReceiverType(),
            PaymentModel\Entity::RECURRING_TYPE => $this->entity->getRecurringType(),
            PaymentModel\Entity::AUTH_TYPE => $this->entity->getAuthType(),
            PaymentModel\Entity::EMI_PLAN . '_' . \RZP\Models\Emi\Entity::DURATION => $this->entity->emiPlan?->getDuration(),
            PaymentModel\Entity::EMI_PLAN . '_' . \RZP\Models\Emi\Entity::ISSUER => $this->entity->emiPlan?->getIssuer(),
            PaymentModel\Entity::WALLET => $this->entity->getWallet(),
            PaymentModel\Entity::ID => $this->entity->getId(),
            PaymentModel\Entity::FEE_BEARER => $this->entity->merchant?->getFeeBearer(),
            PaymentModel\Entity::METHOD => $this->entity->getMethod(),
            PaymentModel\Entity::INTERNATIONAL => $this->entity->isInternational(),
            \RZP\Models\Terminal\Entity::PROCURER => $this->entity->terminal?->getProcurer(),
            \RZP\Models\Order\Entity::PRODUCT_TYPE => $this->entity->order?->getProductType()
        ];
    }

    private function logPricingFailureDetails($pricing)
    {
        $mode = $this->app['rzp.mode'] ?? null;

        $card = $this->getCardDetails();

        $payment_details = $this->getPaymentDetails();

        $features = $this->entity->getPricingFeatures();

        $this->trace->info(TraceCode::PAYMENT_PRICING_RULE_NOT_FOUND,[
            'mode'  => $mode,
            'pricing_plan'  => $pricing->getId(),
            'merchant_id' => $this->entity->getMerchantId(),
            'payment_details' => $payment_details,
            'card_details' => $card,
            'features' => $features
        ]);
    }

    /*
     *  Checks if Fallback Rule should be applied in case
     *  no rule is found in Merchant Pricing Plan
     */
    private function isFallbackRuleExpEnabled(): bool
    {
        $properties = [
            'id' => $this->entity->getMerchantId(),
            'experiment_id' => $this->fallbackStandardPlanExperimentId,
            'request_data'  => json_encode(['mid' => $this->entity->getMerchantId()]),
        ];
        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }

    /*
     * Even though function says "get", no rule is getting returned here.
     * This is because even the parent class function has the same behavior.
     */
    public function getRelevantPricingRule(Pricing\Plan $pricing)
    {
        try{
            parent::getRelevantPricingRule($pricing);
        } catch (Exception\LogicException $e) {

            if ($e->getCode() === ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT) {

                $isExpEnabled = $this->isFallbackRuleExpEnabled();;

                $merchant = $this->entity->merchant;

                $this->trace->count(Metrics::SERVER_ERROR_PRICING_RULE_ABSENT_COUNT,
                    [
                        'org_id' => $merchant?->getOrgId(),
                        'payment_method' => $this->entity->getMethod(),
                        'on_fallback_plan' => $pricing->getId() == Pricing\DefaultPlan::NO_RULE_FALLBACK_PLAN_ID,
                    ]);

                $existingPaymentPricingRule = (!empty($this->pricingRules) && ($this->pricingRules[0]?->getFeature() == Pricing\Feature::PAYMENT));

                if ($merchant?->org->getId() === Org\Entity::RAZORPAY_ORG_ID && $pricing->isTypePricing() && $isExpEnabled === true){

                    // Log Payment And Card Details on Missing Rule Error
                    $this->logPricingFailureDetails($pricing);

                    if ($pricing->getId() != Pricing\DefaultPlan::NO_RULE_FALLBACK_PLAN_ID){

                        $defaultFallbackPlanId = Pricing\DefaultPlan::NO_RULE_FALLBACK_PLAN_ID;

                        if ($merchant->getCountry() === 'SG' && app()->isEnvironmentProduction() === true) {
                            $defaultFallbackPlanId = Pricing\DefaultPlan::SG_NO_RULE_FALLBACK_PLAN_ID;
                        }

                        $this->trace->count(Metrics::FALLBACK_PRICING_APPLIED_COUNT,
                            [
                                'org_id' => $merchant?->org->getId(),
                                'fallback_plan_id' => $defaultFallbackPlanId,
                                'payment_method' => $this->entity->getMethod(),
                            ]);

                        try {
                            $pricing = (new Fee())->getPricingPlanForFeesCalculation($defaultFallbackPlanId, $this->entity, $merchant);
                        } catch (\Exception $e) {
                            $this->trace->info("Pricing plan not fetched from repo",
                                [
                                    "planId" => $defaultFallbackPlanId,
                                    "error" => $e->getMessage(),
                                ]);
                            $defaultFallbackPlanId = Pricing\DefaultPlan::NO_RULE_FALLBACK_PLAN_ID;
                            $pricing = (new Fee())->getPricingPlanForFeesCalculation($defaultFallbackPlanId, $this->entity, $merchant);
                        }

                        try {
                            if($existingPaymentPricingRule){
                                parent::getRelevantPricingRuleOnlyAddon($pricing);
                            }else{
                                parent::getRelevantPricingRule($pricing);
                            }

                        }catch (Exception\LogicException $e) {
                            if ($e->getCode() === ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT) {
                                $this->logPricingFailureDetails($pricing);
                            }
                            throw $e;
                        }
                    }else{
                        throw $e;
                    }
                }else{
                    throw $e;
                }
            }else{
                throw $e;
            }
        }

        $payment = $this->entity;

        try {

            if ($payment->isEligibleForFeeModelOverride())
            {
                $feeModel = $this->validateAndGetFeeModel($this->pricingRules);
                if (empty($feeModel) === false) {
                    $this->trace->info(TraceCode::RULE_LEVEL_FEE_MODEL,
                        [
                            'fee_model' => $feeModel,
                            'merchant_id' => $payment->getMerchantId(),
                            'payment_id' => $payment->getId(),
                            'transaction_id' => $payment->transaction->getId(),
                        ]);
                    $payment->transaction->setFeeModel($feeModel);
                }
            }
        } catch (\Throwable $e){
            $this->trace->error(TraceCode::RULE_LEVEL_FEE_MODEL_FAILURE,
                [
                   'error'=> $e->getMessage()
                ]);
        }

        // this is an side effect that is unavoidable.
        if (($payment->merchant !== null) and
            ($payment->merchant->isFeeBearerDynamic() === true))
        {
            $feeBearer = $this->validateAndGetFeeBearer($this->pricingRules);
        }
        else
        {
            $feeBearer = $payment->merchant->getFeeBearer();
        }

        $payment->setFeeBearer($feeBearer);

        if($payment->getConvenienceFee() !== null)
        {
            $payment->setFeeBearer(FeeBearer::PLATFORM);
        }

    }

    protected function isFeeBearerCustomer()
    {
        $payment = $this->entity;

        return ($payment->isFeeBearerCustomer() === true);
    }

    protected function setAmount()
    {
        $amount = $this->entity->getBaseAmount();
        $fee = $this->entity->getFee();

        // log payment entity and fee details
        if ($this->entity->getEntity() === (Entity::PAYMENT)){
            $merchantFeeBearer = $this->entity->merchant->getFeeBearer();
            $paymentFeeBearer = $this->entity->getFeeBearer(true);

            $currentRoute = '';
            try
            {
                $currentRoute = app('request.ctx')->getRoute();
            }
            catch (\Throwable $e)
            {
                $this->trace->error(TraceCode::GET_ROUTE_NAME_ERROR, [
                    'error' => $e->getMessage()
                ]);
            }

            $this->trace->info(TraceCode::PRICING_SET_AMOUNT_ATTEMPT,[
                'amount' => $amount,
                'fee' => $fee,
                'payment_fee_bearer' => $paymentFeeBearer,
                'merchant_fee_bearer' => $merchantFeeBearer,
                'route' => $currentRoute,
                'convenience_fee' => $this->entity->getConvenienceFee(),
                'convenience_fee_gst' => $this->entity->getConvenienceFeeGst(),
                'payment_id' => $this->entity->getId(),
            ]);
        }

        /*
            1. The first call will have the fee = 0, hence fees will be calculated on the original amount
            2. On validation/capture call, the fee & convenience fee can be set
            3. Remove convenience fee if it is set. Convenience fee is the amount added on payment amount if order config is set
            4. For cases CFB abd DFB merchants when convenience fee is not set, remove mdr fee
            5. MCC payments have initial fees stored in MCC currency, needs to be converted to INR
        */

        if ($this->entity->getEntity() === (Entity::PAYMENT) && $this->entity->getConvenienceFee() !== null){
            $amount = $amount - $this->entity->getConvenienceFee() - $this->entity->getConvenienceFeeGst();
        }

        if ($this->isFeeBearerCustomerOrDynamic() === true && $this->shouldAdjustPaymentFee() === true)
        {
            if ($this->isPartnershipFeeCalculationRoute() === false )
            {
                if ($this->entity->getConvenienceFee() == 0 && $fee != 0){
                    $currency = $this->entity->getCurrency();
                    $baseCurrency = Currency::INR;
                    if (isset($this->entity) === true && isset($this->entity->merchant) === true)
                    {
                        $baseCurrency = $this->entity->merchant->getCurrency();
                    }

                    $input = [];
                    if ($this->entity->merchant->isLRSFlowEnabled() === true)
                    {
                        $input['is_lrs_merchant'] = true;
                        $input['order_id'] = $this->entity->getOrderAttribute()['id'];
                    }

                    $amount = $amount - (new Core)->getBaseAmount($fee, $currency, $baseCurrency, $input);
                }
            }else
            {
                // incase of partnership fee calculation route currency conversion is not required
                // as the fee in payment entity is already in base currency
                $amount = $amount - $fee;
            }
        }

        $this->amount = $amount;
    }

    // commission calculator call from partnership service is made after payment capture
    // Since fee is already set during payment capture and if platform is selected as fee bearer
    // fee should not be adjusted from base amount
    protected function shouldAdjustPaymentFee()
    {
        $payment = $this->entity;
        if ($this->isPartnershipFeeCalculationRoute() && $payment->getFeeBearer(true) === FeeBearer::PLATFORM)
        {
            return false;
        }
        return true;
    }

    protected function isPartnershipFeeCalculationRoute()
    {
        try
        {
            $currentRoute = app('request.ctx')->getRoute();
            return $currentRoute == 'calculate_commission';
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::GET_ROUTE_NAME_ERROR, [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function setRewardAmount()
    {
        if (($this->entity->getEntity() === (Entity::PAYMENT)) and
            ($this->entity->hasReward() === true))
        {
            $this->rewardAmount = $this->entity->reward->getRewardAmount();
        }

    }
    protected function isMerchantProcuredPayment(): bool{

        $payment = $this->entity;

        if ($payment->merchant->isFeeBearerCustomerOrDynamic() === true)
        {
            return false;
        }
        //
        // Transfer method, CoD method doesn't have terminal associated
        //
        if (($payment->getMethod() === PaymentModel\Method::TRANSFER) or
            ($payment->isCoD() === true) or ($payment->getMethod() === PaymentModel\Method::INTL_BANK_TRANSFER))
        {
            return false;
        }
        // this is done to prevent null terminals entity check.
        // ref : https://razorpay.slack.com/archives/C04BDR5TEGL/p1700459478960999
        if (($payment->hasTerminal() === false) or
            ($payment->terminal === null))
        {
         return false;
        }

        $procurer = $payment->terminal->getProcurer();

        if ($procurer === "merchant") {
            return true;
        }

        return false;
    }

    protected function getBasicPricingRuleFiltersForProcuredPayment($product, $feature, $procurer, $method) : array
    {
        $filters = [
            [Pricing\Entity::PRODUCT,        $product,   false, null],
            [Pricing\Entity::FEATURE,        $feature,   false, null],
            [Pricing\Entity::PROCURER,       $procurer,  false, null],
            [Pricing\Entity::PAYMENT_METHOD, $method,    true, null],
        ];

        return $filters;
    }

    protected function isFeatureOptional(string $feature):bool
    {
        $isOptional = Pricing\Feature::isFeaturePricingOptional($feature);

        if ($isOptional === true)
        {
            return true;
        }

        if (($feature === Pricing\Feature::PAYMENT) and ($this->isMerchantProcuredPayment() === true))
        {
            return  true;
        }

        return false;
    }

    private function getBasicPricingRuleFiltersForFeature(string $product, $feature, $method)
    {
        $filters = [
            [Pricing\Entity::PRODUCT, $product, false, null],
            [Pricing\Entity::FEATURE, $feature, false, null]
        ];

        if (in_array($feature, Pricing\Feature::METHOD_AGNOSTIC_FEATURES))
        {
            return array_merge($filters, [
                [Pricing\Entity::PAYMENT_METHOD, $method, true, null]
            ]);

        }
        // For optimizer convenience fee feature, we need to filter based on gateway
        if($feature === Pricing\Feature::OPTIMIZER_CONVENIENCE_FEE && $this->entity->getEntity() === Entity::PAYMENT)
        {
            $gateway = $this->entity->getGateway();
            $filters[] = [Pricing\Entity::GATEWAY, $gateway, true, null];
        }

        $filters[] = [Pricing\Entity::PAYMENT_METHOD, $method,  false, null];

        return $filters;
    }

    protected function isFlexMoneyProvider($issuer): bool
    {
        return (in_array(strtoupper($issuer), self::$flexMoneyIssuers));
    }

    protected function getRelevantPricingRuleForSodexoCardPayment($rules)
    {
        $payment = $this->entity;

        $international = $payment->isInternational();

        $authType = $payment->getAuthType();

        $filters = [
            [Pricing\Entity::INTERNATIONAL,             $international, false,  false   ],
            [Pricing\Entity::PAYMENT_METHOD_TYPE,       PaymentModel\Entity::SODEXO, true, null  ],
            [Pricing\Entity::AUTH_TYPE,                 $authType,      true,   null    ],
        ];

        $sodexoRules = $this->applyFiltersOnRules($rules, $filters);

        if (empty($sodexoRules) === true) {
            $filters = [
                [Pricing\Entity::INTERNATIONAL,             $international, false,  false   ],
                [Pricing\Entity::PAYMENT_METHOD_TYPE,       Card\Type::DEBIT, true, null  ],
                [Pricing\Entity::AUTH_TYPE,                 $authType,      true,   null    ],
            ];
            $rules = $this->applyFiltersOnRules($rules, $filters);
        }
        else {
            $rules = $sodexoRules;
        }

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    private function isMerchantWhitelistedForProcurerPricing($merchantId)
    {
        return in_array($merchantId, self::MERCHANT_PROCURER_MIDS);
    }
}
