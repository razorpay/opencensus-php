<?php

namespace RZP\Models\Ledger\ReverseShadow\Payments;

use Ramsey\Uuid\Uuid;
use Razorpay\Trace\Logger;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BaseException;
use RZP\Models\Base;
use RZP\Models\Ledger\Constants;
use RZP\Models\Merchant;
use RZP\Models\Payment\Gateway;
use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Feature;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Payment;
use RZP\Models\Pricing\Fee;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Models\Transaction\Entity;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;

class Core extends Base\Core
{
    protected $merchant;

    use ReverseShadowTrait;

    const NEGATIVE_BALANCE_ALLOWED_PAYMENT_METHODS = [
        Payment\Method::EMANDATE,
        Payment\Method::NACH,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createLedgerEntryForMerchantCaptureReverseShadow(Payment\Entity $payment, $discount)
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccountsList = $this->getMerchantAccounts($ledgerService, $payment->getMerchantId());
        $merchantAccountBalances = $this->getMerchantAccountBalancesMap($merchantAccountsList);
        $amountCreditsAccounts = $this->getValidAmountCreditsAccounts($merchantAccountsList);

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($payment);

        $this->trace->info(
            TraceCode::CREATING_FEES_BREAKUP_IN_REVERSE_SHADOW,
            [
                'tax'                                   => $tax,
                'fee'                                   => $fee,
                'fee_split'                             => $feesSplit->toArrayPublic()
            ]);

        if ($payment->isDirectSettlement() === true)
        {
            [$moneyParams, $dynamicMoneyParams] = $this->generateMoneyParamsForDSPayment($payment, $merchantAccountBalances, $amountCreditsAccounts, $fee, $tax, $feesSplit);

            $additionalParams = $this->fetchRulesForDSPaymentCredits($payment, $merchantAccountBalances, $fee);
        }
        else
        {
            [$moneyParams, $dynamicMoneyParams] = $this->generateMoneyParamsForNormalPayment($payment, $merchantAccountBalances, $amountCreditsAccounts, $fee, $tax, $discount, $feesSplit);

            $additionalParams = $this->fetchRulesForPaymentCredits($payment, $merchantAccountBalances, $fee, intval($moneyParams[Constants::BASE_AMOUNT]));
        }

        $transactorId = $payment->getPublicId();

        $transactorEvent = Constants::MERCHANT_CAPTURED;

        $merchantCaptureData = array(
            Constants::TRANSACTOR_ID                 => $transactorId,
            Constants::TRANSACTOR_EVENT              => $transactorEvent,
            Constants::MONEY_PARAMS                  => $moneyParams,
            Constants::ADDITIONAL_PARAMS             => (count($additionalParams) > 0) ? $additionalParams : null,
            Constants::LEDGER_INTEGRATION_MODE       => Constants::REVERSE_SHADOW,
            Constants::IDEMPOTENCY_KEY               => Uuid::uuid1(),
            Constants::TENANT                        => Constants::TENANT_PG,
            Constants::NOTES                        => [
                Constants::TRANSACTOR_AMOUNT => $payment->getBaseAmount(),
            ]
        );

        if (isset($feesSplit) === true)
        {
            foreach ($feesSplit as $feeSplit)
            {
                $merchantCaptureData[Constants::NOTES][$feeSplit->getName()] = $feeSplit->getPricingRule();
            }
        }

        if (empty($dynamicMoneyParams) === false)
        {
            $merchantCaptureData[Constants::DYNAMIC_MONEY_PARAMS] = $dynamicMoneyParams;
        }

        $this->validateInsufficientBalance($payment, $moneyParams, $merchantAccountBalances, $additionalParams);


        if ($payment->getGateway() === Gateway::WALLET_OPENWALLET)
        {
            $merchantCaptureData[Constants::NOTES][Constants::GATEWAY] = Gateway::WALLET_OPENWALLET;
        }

        $apiTransactionId = $this->getAPITransactionId($transactorId, $payment);

        if ($apiTransactionId !== null)
        {
            $merchantCaptureData[Constants::API_TRANSACTION_ID] = $apiTransactionId;
        }

        $transactionMessage = $this->generateBaseForJournalEntry($payment, $payment->getCapturedAt());

        $journalPayload = array_merge($transactionMessage, $merchantCaptureData);

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $journalPayload);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);

        return [($fee-$tax), $tax];
    }


    /** validate insufficient balance for normal and DS payments
     * @param $payment
     * @param $moneyParams
     * @param $merchantAccountBalances
     * @param $additionalParams
     * @return void
     * @throws \RZP\Exception\AssertionException
     */
    protected function validateInsufficientBalance($payment, $moneyParams, $merchantAccountBalances, $additionalParams)
    {
        $properties = [
            "id" => $payment->getMerchantId(),
            "experiment_id" => $this->app['config']->get('app.splitz_insufficient_balance_experiment_id'),
        ];

        $variant = (new MerchantCore())->isSplitzExperimentEnable($properties, 'Enable');

        if ($variant === true)
        {
            if (isset($moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]) === true)
            {
                $merchantBalance = $merchantAccountBalances[Constants::MERCHANT_BALANCE];

                if (isset($moneyParams[Constants::MERCHANT_BALANCE_LIMIT]) === true)
                {
                    $merchantBalance += intval($moneyParams[Constants::MERCHANT_BALANCE_LIMIT]);
                }

                $calculatedMerchantBalance = intval($moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]);

                if (isset($additionalParams[Constants::MERCHANT_BALANCE_ACCOUNTING]) === true)
                {
                    $this->trace->info(
                        TraceCode::BALANCE_VALIDATION_FOR_NORMAL_PAYMENTS,
                        [
                            'payment_id'                => $payment->getId(),
                            'merchant_id'               => $payment->getMerchantId(),
                            'money_params'              => $moneyParams,
                            'additional_params'         => $additionalParams,
                            'merchant_account_balance'  => $merchantAccountBalances
                        ]);

                    assertTrue($merchantBalance >= $calculatedMerchantBalance);
                }

                if (isset($additionalParams[Constants::DIRECT_SETTLEMENT_ACCOUNTING]) &&
                    (!isset($additionalParams[Constants::ACCOUNTING]) ||
                        $additionalParams[Constants::ACCOUNTING] !== Constants::HDFC_VAS_DS_CFB_SURCHARGE_FLOW))
                {
                    $this->trace->info(
                        TraceCode::BALANCE_VALIDATION_FOR_DS_PAYMENTS,
                        [
                            'payment_id'                => $payment->getId(),
                            'merchant_id'               => $payment->getMerchantId(),
                            'money_params'              => $moneyParams,
                            'additional_params'         => $additionalParams,
                            'merchant_account_balance'  => $merchantAccountBalances
                        ]);

                    assertTrue($merchantBalance >= $calculatedMerchantBalance);
                }
            }
        }
    }

    private function getMoneyParamsForFeeBreakupAndTax($feeSplit, $totalCommission, $totalTax)
    {
        $moneyParams = [];

        $moneyParams = $this->getDefaultMoneyParamsForFeeBreakupAndTax();

        // In few cases like isCardlessEmiWalnut369 payments, $totalCommission can be zero and $feeSplit can be non-zero
        if ($totalCommission === 0)
        {
            $moneyParams[Constants::COMMISSION] = strval($totalCommission);
            $moneyParams[Constants::TAX] = strval($totalTax);
            return $moneyParams;
        }

        $allocatedCommission = 0;
        $allocatedTax = 0;
        foreach ($feeSplit as $fee)
        {
            if (isset($fee->amount))
            {
                if (array_key_exists(Constants::FEE_TYPE_VS_FORMULAS[$fee->name], $moneyParams)) {
                    $moneyParams[Constants::FEE_TYPE_VS_FORMULAS[$fee->name]] = strval($fee->amount);
                    $allocatedCommission += $fee->amount;
                    continue;
                }

                if (array_key_exists(Constants::TAX_TYPE_VS_FORMULAS[$fee->name], $moneyParams)) {
                    $moneyParams[Constants::TAX_TYPE_VS_FORMULAS[$fee->name]] = strval($fee->amount);
                    $allocatedTax += $fee->amount;
                }
            }
        }
        $moneyParams[Constants::COMMISSION] = strval($totalCommission - $allocatedCommission);
        $moneyParams[Constants::TAX] = strval($totalTax - $allocatedTax);
        return $moneyParams;
    }

    private function getDefaultMoneyParamsForFeeBreakupAndTax()
    {
        $moneyParams = [];
        foreach (Constants::FEE_AND_TAX_FORMULAS as $feeBreakupType)
        {
            $moneyParams[$feeBreakupType] = "0";
        }
        return $moneyParams;
    }

    protected function generateMoneyParamsForDSPayment(Payment\Entity $payment, $merchantAccountBalances, $amountCreditsAccounts, $fee, $tax, $feesSplit): array
    {
        $moneyParams = [];

        $dynamicMoneyParams = [];

        $amount = 0;

        $feeCredits = $merchantAccountBalances[Constants::MERCHANT_FEE_CREDITS];

        $amountCredits = $merchantAccountBalances[Constants::MERCHANT_AMOUNT_CREDITS];

        $commission = $fee - $tax;

        // for optimizer cfb with direct settlement,
        // we need to split the convenience fee and tax
        //
        if ($payment->merchant->isAtLeastOneFeatureEnabled(Feature\Constants::OPTIMIZER_CFB_FEATURES) === true and
            in_array(PricingFeature::OPTIMIZER_CONVENIENCE_FEE, array_column($feesSplit->toArray(), 'name')))
        {
            $optimizerConvenienceFee = $feesSplit->where('name',PricingFeature::OPTIMIZER_CONVENIENCE_FEE)->first()->amount;
            $optimizerConvenienceTax = (int) round(($optimizerConvenienceFee/$commission) * $tax);
            $commission = $commission - $optimizerConvenienceFee;
            $tax = $tax - $optimizerConvenienceTax;
        }

        if ($payment->merchant->isFeatureEnabled(Feature\Constants::VAS_MERCHANT) === true)
        {
            $moneyParams[Constants::MERCHANT_VAS_AMOUNT]        = strval($fee);
            $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));

            return [$moneyParams, $dynamicMoneyParams];
        }

        //Todo: Check with banking team , fee and tax is populated but do not get deducted from balance.
        //Todo: how do we charge this amount from acquirer bank.
        else if ($payment->isHdfcVasDSCustomerFeeBearerSurcharge() === true)
        {
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval(0);
            $moneyParams[Constants::GATEWAY_ACQUIRER_AMOUNT]    = strval($commission+$tax);
            $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));
        }
        else if($this->isPostpaid($payment) === true)
        {
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $commission);
            $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));
        }
        else if (($this->isGratis($amountCredits, $payment->getAmount()) === true) and ($this->shouldDisableAmountCredits($payment) === false))
        {
            $moneyParams[Constants::RAZORPAY_REWARDS]           = strval($payment->getAmount());
            $dynamicMoneyParams = $this->getDynamicMoneyParams($amountCreditsAccounts, $payment->getAmount());
        }
        else if($this->isFeeCredits($feeCredits, $commission + $tax) === true)
        {
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $commission);
            $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));
        }
        // Normal merchant captured scenario (commissions considered)
        else
        {
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval( $commission + $tax);
            $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));
        }

        $moneyParams[Constants::BASE_AMOUNT] = strval($amount);

        return [$moneyParams, $dynamicMoneyParams];
    }

    protected function fetchRulesForDSPaymentCredits(Payment\Entity $payment, $merchantAccountBalances, $fee): array
    {
        $feeCredits = $merchantAccountBalances[Constants::MERCHANT_FEE_CREDITS];

        $amountCredits = $merchantAccountBalances[Constants::MERCHANT_AMOUNT_CREDITS];

        $rule = null;

        $isCommissionApplicable = true;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT;

        if ($payment->merchant->isFeatureEnabled(Feature\Constants::VAS_MERCHANT) === true)
        {
            $rule[Constants::ACCOUNTING] = Constants::VAS_MERCHANT_FLOW;
        }
        else if ($payment->isHdfcVasDSCustomerFeeBearerSurcharge() === true)
        {
            $rule[Constants::ACCOUNTING] = Constants::HDFC_VAS_DS_CFB_SURCHARGE_FLOW;
        }
        else if($this->isPostpaid($payment) === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::POSTPAID;
        }
        else if(($this->isGratis($amountCredits, $payment->getAmount()) === true) and ($this->shouldDisableAmountCredits($payment) === false))
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS_REDEMPTION_V2;
            $isCommissionApplicable = false;
        }
        else if($this->isFeeCredits($feeCredits, $fee))
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::FEE_CREDITS;
        }

        if ($isCommissionApplicable === true)
        {
            $rule[Constants::FEE_BREAKUP] = Constants::TRUE;
        }

        return $rule;
    }

    protected  function generateMoneyParamsForNormalPayment(Payment\Entity $payment, $merchantAccountBalances, $amountCreditsAccounts, $fee, $tax, $discount, $feesSplit): array
    {
        $moneyParams = [];

        $dynamicMoneyParams = [];

        $amount = abs($payment->getBaseAmount());

        $commission = $fee - $tax;

        $moneyParams[Constants::BASE_AMOUNT] = strval($amount);

        $feeCredits = $merchantAccountBalances[Constants::MERCHANT_FEE_CREDITS];

        $amountCredits = $merchantAccountBalances[Constants::MERCHANT_AMOUNT_CREDITS];

        if ($discount !== null)
        {
            $amount = $amount - $discount;

            if (($payment->isCardlessEmiWalnut369() === true) and
                ($payment->merchant->isFeatureEnabled(Feature\Constants::SOURCED_BY_WALNUT369) === true))
            {
                $commission = 0;
                $tax = 0;
            }
        }

        $maxNegativeLimit = $this->getMaxNegativeLimitForPaymentMerchantCapture($payment);

        $creditOrReserveBalanceLoadingPaymentInfo = $this->extractCreditOrReserveBalanceLoadingPaymentInfo($payment);

        //Todo: Check with banking team , fee and tax is populated but do not get deducted from balance.
        //Todo: how do we charge this amount from acquirer bank.
        if ($payment->isHdfcNonDSSurcharge() === true)
        {
            // Fee and tax is always zero for hdfcNonDSSurcharge payments
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(0);
            $moneyParams[Constants::COMMISSION]                 = strval(0);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        else if ($creditOrReserveBalanceLoadingPaymentInfo[Constants::IS_CREDIT_OR_RESERVE_BALANCE_LOADING_PAYMENT] === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        else if($this->isPostPaidDynamicFeeBearerFlag($payment,$payment->merchant))
        {
            $customerFeeAndGstArray = $payment->getCustomerFeeAndCustomerFeeGst();

            $customerFee = $customerFeeAndGstArray[0];
            $customerTax = $customerFeeAndGstArray[1];

            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount - ($customerFee + $customerTax));
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $commission - ($customerFee + $customerTax));
            $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));
        }
        else if($this->isPrepaidDynamicFeeBearerFlag($payment))
        {
            $customerFeeAndGstArray = $payment->getCustomerFeeAndCustomerFeeGst();

            $customerFee = $customerFeeAndGstArray[0];
            $customerTax = $customerFeeAndGstArray[1];
            $merchantFee = $fee - $customerFee - $customerTax;

            $moneyParams[Constants::GMV_AMOUNT] = strval($amount);

            if ($this->isGratis($amountCredits, $amount) and ($this->shouldDisableAmountCredits($payment) === false))
            {
                // In case of amount credits, only customer fee and tax is deducted
                // Merchant side fee and tax is waived off.

                $merchantAmount = $amount - $customerFee - $customerTax;

                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($merchantAmount);
                $moneyParams[Constants::RAZORPAY_REWARDS]           = strval($amount);
                $moneyParams[Constants::TAX] = strval(abs($customerTax));
                $moneyParams[Constants::COMMISSION] = strval(abs($customerFee));
                $dynamicMoneyParams = $this->getDynamicMoneyParams($amountCreditsAccounts, $amount);
            }
            else if ($this->isFeeCredits($feeCredits, $merchantFee) === true)
            {
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT] = strval($amount - ($customerFee + $customerTax));
                $moneyParams[Constants::FEE_CREDITS] = strval($commission + $tax - ($customerFee + $customerTax));
                $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));
            }
            else
            {
                // Simplifying the expression -
                // the payment amount is summation of amount and the customer fee.
                // We initially obtain the customer fee and tax and subtract it from the total fee and tax to get merchantFee and tax.
                // $merchantFeeAndTax = $fee + $tax - ($customerFee + $customerTax);

                // the merchant balance amount will be equivalent to
                // (total payment amount) - (customer side fee and tax) - (merchant fee and tax)
                //  => $amount - (customerFee + customerTax) - ($merchantFeeAndTax)
                //  => $amount - (customerFee + customerTax) - ($fee + $tax - ($customerFee + $customerTax))
                //  => $amount - $fee - $tax
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT] = strval($amount - $commission - $tax);
                $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));
            }
        }
        else if($this->isPostpaid($payment) === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $commission);
            $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));
        }
        else if ($this->isGratisWithoutCustomerFeeBearer($amountCredits, $amount, $payment) and ($this->shouldDisableAmountCredits($payment) === false))
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::RAZORPAY_REWARDS]           = strval($amount);
            $dynamicMoneyParams = $this->getDynamicMoneyParams($amountCreditsAccounts, $amount);
        }
        else if($this->isFeeCreditsWithoutCustomerFeeBearer($feeCredits, $commission + $tax, $payment) === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $commission);
            $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));
        }
        // Normal merchant captured scenario (commissions considered)
        else
        {
            // Use case where amount is less than fee charged, hence we need to deduct more money from merchant balance
            // Use case has method as bank transfer

            // Use case where amount is 0, happens for first payment in emandate subscriptions
            if($amount === 0)
            {
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($commission + $tax);
            }
            else if($amount < ($commission + $tax))
            {
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT] = strval($commission + $tax - $amount);
            }
            // Normal use case, amount is greater than (commission and tax)
            // We credit merchant balance in this case after deducting the fee.
            else
            {
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount - $commission - $tax);
            }

            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams = array_merge($moneyParams, $this->getMoneyParamsForFeeBreakupAndTax($feesSplit, $commission, $tax));

            if ($maxNegativeLimit !== null)
            {
                $moneyParams[Constants::MERCHANT_BALANCE_LIMIT] = strval($maxNegativeLimit);
            }
        }

        return [$moneyParams, $dynamicMoneyParams];
    }

    protected function fetchRulesForPaymentCredits(Payment\Entity $payment, $merchantAccountBalances, $fee, $amount): array
    {
        $rule = [];

        $feeCredits = $merchantAccountBalances[Constants::MERCHANT_FEE_CREDITS];

        $amountCredits = $merchantAccountBalances[Constants::MERCHANT_AMOUNT_CREDITS];

        $additionalParams = $this->getAdditionalGmvAccountingParams($payment);

        $isCommissionApplicable = true;

        if (count($additionalParams) > 0)
        {
            return $additionalParams;
        }

        if ($payment->isHdfcNonDSSurcharge() === true)
        {
             $rule[Constants::ACCOUNTING] = Constants::HDFC_NON_DS_SURCHARGE_FLOW;
             $isCommissionApplicable = false;
        }
        else if($this->isPostpaid($payment) === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::POSTPAID;
        }
        else if($this->isPrepaidDynamicFeeBearerFlag($payment) && $this->isGratis($amountCredits, $amount) and ($this->shouldDisableAmountCredits($payment) === false))
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::DFB_AMOUNT_CREDITS_V2;
            $isCommissionApplicable = false;
        }
        else if ($this->isGratisWithoutCustomerFeeBearer($amountCredits, $amount, $payment) and ($this->shouldDisableAmountCredits($payment) === false))
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS_REDEMPTION_V2;
            $isCommissionApplicable = false;
        }
        else if($this->isFeeCreditsWithoutCustomerFeeBearer($feeCredits, $fee, $payment))
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::FEE_CREDITS;
        }
        else if ($amount === 0)
        {
            $rule[Constants::MERCHANT_BALANCE_ACCOUNTING] = Constants::ZERO_AMOUNT_PAYMENT;
        }
        else if($amount < $fee)
        {
            $rule[Constants::MERCHANT_BALANCE_ACCOUNTING] = Constants::BALANCE_DEDUCT;
        }

        if ($isCommissionApplicable === true)
        {
            $rule[Constants::FEE_BREAKUP] = Constants::TRUE;
        }


        return $rule;
    }

    public function createLedgerEntryForGatewayCaptureReverseShadow(Payment\Entity $payment, $apiTransactionId = null)
    {
        if ($payment->isDirectSettlement() === true)
        {
            return [];
        }

        //add ledger-outbox event check here to avoid double gateway_captured request
        $transactorId = $payment->getPublicId();
        $transactorEvent =  Constants::GATEWAY_CAPTURED;
        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);
        $gatewayCaptureOutboxEntries = $this->repo->ledger_outbox->fetchOutboxEntriesByPayloadNameWithTrashed($payloadName);
        if (count($gatewayCaptureOutboxEntries) > 0)
        {
            return [];
        }

        if ($apiTransactionId === null)
        {
            $apiTransactionId =  UniqueIdEntity::generateUniqueId();
        }

        $gateway = $payment->terminal ? $payment->terminal->getGateway() : "not found";

        $additionalParams = $this->getAdditionalGmvAccountingParams($payment);

        $journalPayload = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::MERCHANT_ID                  => $payment->getMerchantId(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::TRANSACTION_DATE             => $payment->getUpdatedAt(),
            Constants::ADDITIONAL_PARAMS            => (count($additionalParams) > 0) ? $additionalParams : null,
            Constants::API_TXN_ID                   => $apiTransactionId,
            Constants::IDENTIFIERS                  => [
                Constants::GATEWAY          => $gateway,
            ],
            Constants::MONEY_PARAMS                 => [
                Constants::AMOUNT           => strval($payment->getBaseAmount()),
                Constants::BASE_AMOUNT      => strval($payment->getBaseAmount()),
            ],
            Constants::LEDGER_INTEGRATION_MODE      => Constants::REVERSE_SHADOW,
            Constants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
            Constants::TENANT                       => Constants::TENANT_PG,
            Constants::NOTES                        => [
                Constants::API_TXN_ID    => $apiTransactionId,
                Constants::TRANSACTOR_AMOUNT => $payment->getBaseAmount(),
            ]
        );

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $journalPayload);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
    }

    public  function createLedgerEntryForCaptureGatewayCommissionReverseShadow(Payment\Entity $payment, $reconGatewayFee, $reconGatewayServiceTax)
    {
        $transactorId = $payment->getPublicId();

        $transactorEvent =  Constants::GATEWAY_CAPTURED_COMMISSION;

        $gateway = $payment->terminal ? $payment->terminal->getGateway() : "not found";

        $gatewayCommission = $reconGatewayFee ?? 0;

        $gatewayTax = $reconGatewayServiceTax ?? 0;

        if(($gatewayCommission === 0) and ($gatewayTax === 0))
        {
            return;
        }

        $gatewayReceivableAmount = $gatewayCommission + $gatewayTax;

        $journalPayload = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::MERCHANT_ID                  => $payment->getMerchantId(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::TRANSACTION_DATE             => $payment->getUpdatedAt(),
            Constants::IDENTIFIERS                  => [
                Constants::GATEWAY          => $gateway,
            ],
            Constants::MONEY_PARAMS                 => [
                Constants::AMOUNT             => strval($gatewayReceivableAmount),
                Constants::BASE_AMOUNT        => strval($gatewayReceivableAmount),
                Constants::GATEWAY_COMMISSION => strval($gatewayCommission),
                Constants::GATEWAY_TAX        => strval($gatewayTax)

            ],
            Constants::LEDGER_INTEGRATION_MODE      => Constants::REVERSE_SHADOW,
            Constants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
            Constants::TENANT                       => Constants::TENANT_PG,
        );

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $journalPayload);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
    }

    // This function identifies payments made by any merchant to a razorpay internal merchant
    public function extractCreditOrReserveBalanceLoadingPaymentInfo(Payment\Entity $payment) : array
    {
        if($payment->getNotes() === null)
        {
            return [
                Constants::IS_CREDIT_OR_RESERVE_BALANCE_LOADING_PAYMENT    => false
            ];
        }

        $notes = $payment->getNotes()->toArray();

        $type = (isset($notes["type"]) === true) ? $notes["type"] : "";

        if(($type === Constants::FEE_CREDIT) or
            ($type === Constants::REFUND_CREDIT) or
            ($type === Constants::RESERVE_BALANCE))
        {
            return [
                Constants::IS_CREDIT_OR_RESERVE_BALANCE_LOADING_PAYMENT    => true,
                Constants::TYPE                                            => $type
            ];
        }

        return [
            Constants::IS_CREDIT_OR_RESERVE_BALANCE_LOADING_PAYMENT    => false
        ];
    }

    protected function getAdditionalGmvAccountingParams(Payment\Entity $payment)
    {
        $rule = [];

        $creditOrReserveBalanceLoadingPaymentInfo = $this->extractCreditOrReserveBalanceLoadingPaymentInfo($payment);

        if($creditOrReserveBalanceLoadingPaymentInfo[Constants::IS_CREDIT_OR_RESERVE_BALANCE_LOADING_PAYMENT] === true)
        {
            $type = $creditOrReserveBalanceLoadingPaymentInfo[Constants::TYPE];

            if($type === Constants::FEE_CREDIT)
            {
                $rule[Constants::GMV_ACCOUNTING] = Constants::FEE_CREDIT_GMV;
            }
            else if($type === Constants::REFUND_CREDIT)
            {
                $rule[Constants::GMV_ACCOUNTING] = Constants::REFUND_CREDIT_GMV;
            }
            else if($type === Constants::RESERVE_BALANCE)
            {
                $rule[Constants::GMV_ACCOUNTING] = Constants::RESERVE_BALANCE_GMV;
            }
        }

        return $rule;
    }

    private function getMaxNegativeLimitForPaymentMerchantCapture(Payment\Entity $payment)
    {
        if ((in_array($payment->getMethod(), self::NEGATIVE_BALANCE_ALLOWED_PAYMENT_METHODS) === false) or
            ($payment->isRecurringTypeInitial() === false)) {
            return null;
        }

        $balance = $payment->merchant->getBalanceByTypeOrFail(Type::PRIMARY);

        $maxNegative = (new BalanceConfig\Core())->getMaxNegativeAmountAutoForBalanceId($balance->getId());

        if ($maxNegative === 0) {
            $maxNegative = BalanceConfig\Entity::DEFAULT_MAX_NEGATIVE;
        }
        return $maxNegative;

    }

    public function shouldDisableAmountCredits(Payment\Entity $payment):bool
    {
        $merchant = $payment->merchant;

        $merchantDetail = $merchant->merchantDetail;

        if (isset($merchantDetail) === false)
        {
            return false;
        }

        if ($merchant->isRazorpayOrgId() === true && $merchantDetail->isUnregisteredBusiness() === false)
        {
            if (isset($payment->card) === true and ($payment->card->isPrepaid() === true or $payment->card->isSubTypeBusiness() === true))
            {
                $this->trace->info(
                    TraceCode::BLOCKING_AMOUNT_CREDIT_FOR_PAYMENTS,
                    [
                        'payment_id' => $payment->getId(),
                        'merchant_id' => $merchant->getId()
                    ]);

                return true;
            }
        }

        if (($merchantDetail->isUnregisteredBusiness() === true)
            and (new Merchant\Core())->isDisableFreeCreditsFeatureEnabled($merchant, Feature\Constants::DISABLE_FREE_CREDIT_UNREG) === true)
        {
            return $this->isMethodCreditCard($payment);
        }
        else if (($merchantDetail->isUnregisteredBusiness() === false)
            and (new Merchant\Core())->isDisableFreeCreditsFeatureEnabled($merchant, Feature\Constants::DISABLE_FREE_CREDIT_REG) === true)
        {
            return $this->isMethodCreditCard($payment);
        }

        return false;
    }

    public function isMethodCreditCard(Payment\Entity $payment) : bool
    {
        if ($payment->isMethodCardOrEmi() === false)
        {
            return false;
        }

        $card = $payment->card;

        if (isset($card) === true and $card->isCredit() === true)
        {
            return true;
        }

        return false;
    }

    public function fetchLedgerJournalForPaymentMerchantCapture($payment)
    {
        $requestHeaders = [
            Ledger\Base::LEDGER_TENANT_HEADER => 'PG',
        ];

        $ledgerInput = [
            Ledger\Base::TRANSACTOR_ID    => $payment->getPublicId(),
            Ledger\Base::MERCHANT_ID      => $payment->merchant->getId(),
            Ledger\Base::TRANSACTOR_EVENT => LedgerConstants::MERCHANT_CAPTURED
        ];

        try
        {
            $response = $this->app['ledger']->fetchByTransactor($ledgerInput, $requestHeaders, true);
        }
        catch (\Throwable $ex)
        {
            if ($ex instanceof BaseException)
            {
                $exceptionData = $ex->getData();

                // If no journal found
                if (str_contains($exceptionData['response_body']['msg'], 'record_not_found'))
                {
                    return null;
                }
            }

            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::FETCH_JOURNAL_FAILED,
                [
                    'payment_id'  => $payment->getId(),
                ]);

            return null;
        }

        return $response['body'];
    }
}
