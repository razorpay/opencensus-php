<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Constants\Mode;
use RZP\Exception;
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
use RZP\Models\Payment as PaymentModel;
use RZP\Constants\Entity ;

// Terminal Calculator extends Payment Calculator.
// Take extra care while modifying existing logic.
class Payment extends Base
{
    const FLEXMONEY      = 'flexmoney';
    const HCIN_IFSC      = 'HCIN';
    const KRBE_IFSC      = 'KRBE';
    const CSHE_IFSC      = 'CSHE';
    const TVSC_IFSC      = 'TVSC';


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

            $mode = $this->mode ?? Mode::LIVE;

            $featureFlag = "apply_procurer_pricing";

            $variant = $this->app->razorx->getTreatment($merchantId, $featureFlag, $mode);

            if ($variant === "on")
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

        $rule = $this->getPricingRule($rules, $method);

        if ($rule === null)
        {
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

            if (count($rules) > 0)
            {
                $rule = $this->getPricingRule($rules, $method);

                $this->pricingRules->push($rule);
            }
        }
    }

    protected function getPricingRule($rules, $method)
    {
        $rules = $this->getRelevantPricingRuleForProcurer($rules);

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
        else
        {
            $rule = $this->validateAndGetOnePricingRule($rules);
        }

        return $rule;
    }

    protected function getRelevantPricingRuleForProcurer($rules)
    {
        $payment = $this->entity;

        if ($payment->merchant->isFeeBearerCustomerOrDynamic() === true)
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

        $procurer = $payment->terminal->getProcurer();

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

        if ($network === Card\Network::AMEX)
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

        return $this->validateAndGetOnePricingRule($rules);
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
        }

        if ($payment->isCreditCardOnUpi()=== true)
        {

            if ($payment->checkIfCCOnUPIPricingSplitzExperimentEnabled() === true)
            {
                $receiverType = PaymentsUpi\PayerAccountType::PRICING_PLAN_RECEIVER_TYPE_CREDIT;
            }
        }

        $filters1 = [
            [Pricing\Entity::RECEIVER_TYPE, $receiverType, true, null],
        ];

        $recurringType = $payment->getRecurringType();

        if($payment->isUpiRecurring())
        {
            if($payment->getRecurringType() === PaymentModel\RecurringType::CARD_CHANGE)
            {
                $recurringType = PaymentModel\RecurringType::INITIAL;
            }

            $upiAutopayPricingVariant = $this->app->razorx->getTreatment(
                $payment->getMerchantId(),
                RazorxTreatment::UPI_AUTOPAY_PRICING_BLACKLIST,
                $this->mode,
                3
            );

            if($upiAutopayPricingVariant === "on")
            {
                $recurringType = null;
            }
        }

        // this is to filter upi recurring (initial/auto) or onetime upi pricing rule
        $filters1[] = [Pricing\Entity::PAYMENT_METHOD_SUBTYPE, $recurringType, false, null];

        $rules = $this->applyFiltersOnRules($rules, $filters1);
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
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

        //Emi duration and issuer filter is for merchant subvented model
        //in normal emi it will be null where feature is payment
        $filters1 = array(
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

    /*
     * Even though function says "get", no rule is getting returned here.
     * This is because even the parent class function has the same behavior.
     */
    public function getRelevantPricingRule(Pricing\Plan $pricing)
    {
        parent::getRelevantPricingRule($pricing);

        $payment = $this->entity;

        try {

            if ($payment->isEligibleForFeeModelOverride())
            {
                $feeModel = $this->validateAndGetFeeModel($this->pricingRules);

                if (empty($feeModel) === false) {

                    $mode = $this->app['rzp.mode'] ?? null;
                    $feeModelOverride = $this->app->razorx->getTreatment($payment->getMerchantId(), RazorxTreatment::FEE_MODEL_OVERRIDE, $mode);

                    if ($feeModelOverride == RazorxTreatment::RAZORX_VARIANT_ON) {

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

        if ($this->isFeeBearerCustomerOrDynamic() === true)
        {
            // 1. The first call will have the fee = 0,
            //    hence fees will be calculated on the original amount
            // 2. On validation/capture call, the fee will be set
            // 3. MCC payments have initial fees stored in MCC currency, needs to be converted to INR

            $fee = $this->entity->getFee();
            $currency = $this->entity->getCurrency();

            $baseCurrency = Currency::INR;

            if (isset($this->entity) === true && isset($this->entity->merchant) === true)
            {
                $baseCurrency = $this->entity->merchant->getCurrency();
            }

            $amount = $amount - (new Core)->getBaseAmount($fee, $currency, $baseCurrency);
        }

        if ($this->entity->getEntity() === (Entity::PAYMENT))
        {
            $amount = $this->entity->getBaseAmountForFeeCalculation($amount);
        }

        $this->amount = $amount;
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
        if ($feature == Pricing\Feature::OPTIMIZER)
        {
            $filters = [
                [Pricing\Entity::PRODUCT,        $product, false, null],
                [Pricing\Entity::FEATURE,        $feature, false, null],
                [Pricing\Entity::PAYMENT_METHOD, $method,  true, null],
            ];

            return $filters;
        }

        $filters = [
            [Pricing\Entity::PRODUCT,        $product, false, null],
            [Pricing\Entity::FEATURE,        $feature, false, null],
            [Pricing\Entity::PAYMENT_METHOD, $method,  false, null],
        ];

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
}
