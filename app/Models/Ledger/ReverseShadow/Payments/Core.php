<?php

namespace RZP\Models\Ledger\ReverseShadow\Payments;

use Ramsey\Uuid\Uuid;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Pricing\Fee;
use RZP\Models\Ledger\Constants;
use RZP\Models\Transaction\Entity;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;

class Core extends Base\Core
{
    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createLedgerEntryForMerchantCaptureReverseShadow(Payment\Entity $payment, $discount)
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccountBalances = $this->getMerchantAccountBalances($ledgerService, $payment->getMerchantId());

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($payment);

        if ($payment->isDirectSettlement() === true)
        {
            $moneyParams = $this->generateMoneyParamsForDSPayment($payment, $merchantAccountBalances, $fee, $tax);

            $additionalParams = $this->fetchRulesForDSPaymentCredits($payment, $merchantAccountBalances, $fee);
        }
        else
        {
            $moneyParams = $this->generateMoneyParamsForNormalPayment($payment, $merchantAccountBalances, $fee, $tax, $discount);

            $additionalParams = $this->fetchRulesForPaymentCredits($payment, $merchantAccountBalances, $fee, $moneyParams[Constants::BASE_AMOUNT]);
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
        );

        $apiTransactionId = $this->getAPITransactionId($transactorId);

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

    protected function generateMoneyParamsForDSPayment(Payment\Entity $payment, $merchantAccountBalances, $fee, $tax): array
    {
        $moneyParams = [];

        $amount = 0;

        $feeCredits = $merchantAccountBalances[Constants::MERCHANT_FEE_CREDITS];

        $amountCredits = $merchantAccountBalances[Constants::MERCHANT_AMOUNT_CREDITS];

        $fee = $fee - $tax;

        //Todo: Check with banking team , fee and tax is populated but do not get deducted from balance.
        //Todo: how do we charge this amount from acquirer bank.
        if (($payment->merchant->isFeatureEnabled(Feature\Constants::VAS_MERCHANT) === true) or
            ($payment->isHdfcVasDSCustomerFeeBearerSurcharge() === true))
        {
            if ($this->isPostpaid($payment) === true)
            {
                $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval(0);
            }
            else
            {
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval(0);
            }
            $moneyParams[Constants::TAX]                        = strval(0);
            $moneyParams[Constants::COMMISSION]                 = strval(0);
        }
        else if($this->isPostpaid($payment) === true)
        {
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
        }
        //Todo: Amount Credits is still not solutionised
        else if ($this->isGratis($amountCredits, $fee) === true)
        {
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval(0);
            $moneyParams[Constants::TAX]                        = strval(0);
            $moneyParams[Constants::COMMISSION]                 = strval(0);
        }
        else if($this->isFeeCredits($feeCredits, $fee) === true)
        {
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $fee);
        }
        // Normal merchant captured scenario (commissions considered)
        else
        {
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval( $fee + $tax);
        }

        $moneyParams[Constants::BASE_AMOUNT] = strval($amount);

        return $moneyParams;
    }

    protected function fetchRulesForDSPaymentCredits(Payment\Entity $payment, $merchantAccountBalances, $fee): array
    {
        $feeCredits = $merchantAccountBalances[Constants::MERCHANT_FEE_CREDITS];

        $rule = null;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT;

        if($this->isPostpaid($payment) === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::POSTPAID;
        }
        else if($this->isFeeCredits($feeCredits, $fee))
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::FEE_CREDITS;
        }

        return $rule;
    }

    protected  function generateMoneyParamsForNormalPayment(Payment\Entity $payment, $merchantAccountBalances, $fee, $tax, $discount): array
    {
        $moneyParams = [];

        $amount = abs($payment->getBaseAmount());

        $fee = $fee - $tax;

        $moneyParams[Constants::BASE_AMOUNT] = strval($amount);

        $feeCredits = $merchantAccountBalances[Constants::MERCHANT_FEE_CREDITS];

        $amountCredits = $merchantAccountBalances[Constants::MERCHANT_AMOUNT_CREDITS];

        if ($discount !== null)
        {
            $amount = $amount - $discount;

            if (($payment->isCardlessEmiWalnut369() === true) and
                ($payment->merchant->isFeatureEnabled(Feature\Constants::SOURCED_BY_WALNUT369) === true))
            {
                $fee = 0;
                $tax = 0;
            }
        }

        $creditLoadingPaymentInfo = $this->extractCreditLoadingPaymentInfo($payment);

        //Todo: Check with banking team , fee and tax is populated but do not get deducted from balance.
        //Todo: how do we charge this amount from acquirer bank.
        if ($payment->isHdfcNonDSSurcharge() === true)
        {
            if ($this->isFeeCredits($feeCredits, $fee) === true)
            {
                $moneyParams[Constants::FEE_CREDITS]                = strval(0);
            }
            else if ($this->isPostpaid($payment) === true)
            {
                $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval(0);
            }
            else
            {
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval(0);
            }
            $moneyParams[Constants::GMV_AMOUNT]                 = strval(0);
            $moneyParams[Constants::TAX]                        = strval(0);
            $moneyParams[Constants::COMMISSION]                 = strval(0);
        }
        else if ($creditLoadingPaymentInfo[Constants::IS_CREDIT_LOADING_PAYMENT] === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        else if($this->isPostPaidDynamicFeeBearerFlag($payment,$payment->merchant))
        {
            $customerFeeAndGstArray = $this->getCustomerFeeAndCustomerFeeGst($payment,$fee, $tax);

            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount - $customerFeeAndGstArray[0] - $customerFeeAndGstArray[1]);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee - $customerFeeAndGstArray[0] - $customerFeeAndGstArray[1]);
        }
        else if($this->isPostpaid($payment) === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
        }
        else if ($this->isGratisWithoutCustomerFeeBearer($amountCredits, $amount, $payment))
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        else if($this->isFeeCreditsWithoutCustomerFeeBearer($feeCredits, $fee, $payment) === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $fee);
        }
        // Normal merchant captured scenario (commissions considered)
        else
        {
            // Use case where amount is less than fee charged, hence we need to deduct more money from merchant balance
            // Use case has method as bank transfer
            if($amount < ($fee + $tax))
            {
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT] = strval($fee + $tax - $amount);
            }
            // Use case where amount is 0, happens for first payment in emandate subscriptions
            else if($amount === 0)
            {
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($fee + $tax);
            }
            // Normal use case, amount is greater than (commission and tax)
            // We credit merchant balance in this case after deducting the fee.
            else
            {
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount - $fee - $tax);
            }

            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
        }

        return $moneyParams;
    }

    protected function fetchRulesForPaymentCredits(Payment\Entity $payment, $merchantAccountBalances, $fee, $amount): array
    {
        $feeCredits = $merchantAccountBalances[Constants::MERCHANT_FEE_CREDITS];

        $amountCredits = $merchantAccountBalances[Constants::MERCHANT_AMOUNT_CREDITS];

        $rule = [];

        $creditLoadingPaymentInfo = $this->extractCreditLoadingPaymentInfo($payment);

        if($creditLoadingPaymentInfo[Constants::IS_CREDIT_LOADING_PAYMENT] === true)
        {
            $type = $creditLoadingPaymentInfo[Constants::TYPE];

            if($type === Constants::FEE_CREDIT)
            {
                $rule[Constants::GMV_ACCOUNTING] = Constants::FEE_CREDIT_GMV;
            }
            else if($type === Constants::REFUND_CREDIT)
            {
                $rule[Constants::GMV_ACCOUNTING] = Constants::REFUND_CREDIT_GMV;
            }

            return $rule;
        }

        if($this->isPostpaid($payment) === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::POSTPAID;
        }
        else if ($this->isGratisWithoutCustomerFeeBearer($amountCredits, $amount, $payment))
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS;
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

        return $rule;
    }

    public function createLedgerEntryForGatewayCaptureReverseShadow(Payment\Entity $payment)
    {
        if ($payment->isDirectSettlement() === true)
        {
            return [];
        }

        $apiTransactionId =  UniqueIdEntity::generateUniqueId();;

        $transactorId = $payment->getPublicId();

        $transactorEvent =  Constants::GATEWAY_CAPTURED;

        $gateway = $payment->terminal ? $payment->terminal->getGateway() : "not found";

        $additionalParams = [];

        $creditLoadingPaymentInfo = $this->extractCreditLoadingPaymentInfo($payment);

        if($creditLoadingPaymentInfo[Constants::IS_CREDIT_LOADING_PAYMENT] === true)
        {
            $type = $creditLoadingPaymentInfo[Constants::TYPE];

            if($type === Constants::FEE_CREDIT)
            {
                $additionalParams[Constants::GMV_ACCOUNTING] = Constants::FEE_CREDIT_GMV;
            }
            else if($type === Constants::REFUND_CREDIT)
            {
                $additionalParams[Constants::GMV_ACCOUNTING] = Constants::REFUND_CREDIT_GMV;
            }
        }

        $journalPayload = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::MERCHANT_ID                  => $payment->getMerchantId(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::TRANSACTION_DATE             => $payment->getUpdatedAt(),
            Constants::ADDITIONAL_PARAMS             => (count($additionalParams) > 0) ? $additionalParams : null,
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
        );

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $journalPayload);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
    }

    // This function identifies payments made by any merchant to a razorpay internal merchant
    public function extractCreditLoadingPaymentInfo(Payment\Entity $payment) : array
    {
        if($payment->getNotes() === null)
        {
            return [
                Constants::IS_CREDIT_LOADING_PAYMENT    => false
            ];
        }

        $notes = $payment->getNotes()->toArray();

        $type = (isset($notes["type"]) === true) ? $notes["type"] : "";

        if($type === Constants::FEE_CREDIT or $type === Constants::REFUND_CREDIT)
        {
            return [
                Constants::IS_CREDIT_LOADING_PAYMENT    => true,
                Constants::TYPE                         => $type
            ];
        }

        return [
            Constants::IS_CREDIT_LOADING_PAYMENT    => false
        ];
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
}
