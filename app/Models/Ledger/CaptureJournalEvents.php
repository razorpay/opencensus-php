<?php

namespace RZP\Models\Ledger;

use App;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Ledger\Constants as LedgerConstants;

class CaptureJournalEvents
{
    const NEGATIVE_BALANCE_ALLOWED_PAYMENT_METHODS = [
        Payment\Method::EMANDATE,
        Payment\Method::NACH,
    ];

    public static function createTransactionMessageForMerchantCapture(Payment\Entity $payment, Transaction\Entity $transaction, $discount): array
    {
        if ($payment->isDirectSettlement() === true)
        {
            $moneyParams = self::generateMoneyParamsForCaptureDirectSettlement($payment,$transaction);

            $additionalParams = self::fetchRulesForPaymentCreditsDS($transaction);
        }
        else
        {
            $moneyParams = self::generateMoneyParamsForCapture($payment, $transaction, $discount);

            $additionalParams = self::fetchRulesForPaymentCredits($transaction, $payment);
        }

        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        $merchantCaptureData = array(
            Constants::TRANSACTOR_ID                 => $payment->getPublicId(),
            Constants::TRANSACTOR_EVENT              => Constants::MERCHANT_CAPTURED,
            Constants::MONEY_PARAMS                  => $moneyParams,
        );

        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS] = $additionalParams;

        return array_merge($transactionMessage, $merchantCaptureData);
    }

    public static function createTransactionMessageForGatewayCapture(Payment\Entity $payment): array
    {
        if ($payment->isDirectSettlement() === true)
        {
            return [];
        }

        $additionalParams = (new CaptureJournalEvents())->getAdditionalGmvAccountingParams($payment);

        $gateway = $payment->terminal ? $payment->terminal->getGateway() : "not found";

        // api transaction id is assigned to transaction id if it is present in payment entity else payment id is passed as api transaction id
        $message = array(
            Constants::TRANSACTOR_ID                => $payment->getPublicId(),
            Constants::MERCHANT_ID                  => $payment->getMerchantId(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::TRANSACTOR_EVENT             => Constants::GATEWAY_CAPTURED,
            Constants::ADDITIONAL_PARAMS             => (count($additionalParams) > 0) ? $additionalParams : null,
            Constants::TRANSACTION_DATE             => $payment->getCreatedAt(),
            Constants::IDENTIFIERS                  => [
                Constants::GATEWAY          => $gateway,
            ],
            Constants::MONEY_PARAMS                 => [
                Constants::AMOUNT           => strval($payment->getBaseAmount()),
                Constants::BASE_AMOUNT      => strval($payment->getBaseAmount()),
            ]
        );

        return $message;
    }

    public static function createTransactionMessageForCaptureGatewayCommission(Payment\Entity $payment, Transaction\Entity $transaction): array
    {
        $gateway = $payment->terminal ? $payment->terminal->getGateway() : "not found";

        $gatewayCommission = $transaction->getGatewayFee();

        $gatewayTax = $transaction->getGatewayServiceTax();

        $gatewayReceivableAmount = $gatewayCommission + $gatewayTax;

        // api transaction id is assigned to transaction id if it is present in payment entity else payment id is passed as api transaction id
        $message = array(
            Constants::TRANSACTOR_ID                => $payment->getPublicId(),
            Constants::MERCHANT_ID                  => $payment->getMerchantId(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::TRANSACTOR_EVENT             => Constants::GATEWAY_CAPTURED_COMMISSION,
            Constants::TRANSACTION_DATE             => $transaction->getUpdatedAt(),
            Constants::IDENTIFIERS                  => [
                Constants::GATEWAY          => $gateway,
            ],
            Constants::MONEY_PARAMS                 => [
                Constants::AMOUNT             => strval($gatewayReceivableAmount),
                Constants::BASE_AMOUNT        => strval($gatewayReceivableAmount),
                Constants::GATEWAY_COMMISSION => strval($gatewayCommission),
                Constants::GATEWAY_TAX        => strval($gatewayTax)

            ]
        );

        return $message;
    }

    public static function fetchRulesForPaymentCredits(Transaction\Entity $transaction, Payment\Entity $payment)
    {
        $rule = (new CaptureJournalEvents())->getAdditionalGmvAccountingParams($payment);

        if (count($rule) > 0)
        {
            return $rule;
        }

        if($transaction->isFeeCredits() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::FEE_CREDITS;
        }
        else if($transaction->isPostpaid() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::POSTPAID;
        }
        else if($transaction->isGratis() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS_REDEMPTION;
        }
        else if ($transaction->getAmount() === 0)
        {
            $rule[Constants::MERCHANT_BALANCE_ACCOUNTING] = Constants::ZERO_AMOUNT_PAYMENT;
        }
        else if($transaction->getAmount() < $transaction->getFee())
        {
            $rule[Constants::MERCHANT_BALANCE_ACCOUNTING] = Constants::BALANCE_DEDUCT;
        }

        return $rule;
    }

    public static function fetchRulesForPaymentCreditsDS(Transaction\Entity $transaction)
    {
        $rule = null;

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT;

        if($transaction->isGratis() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS_REDEMPTION;
        }
        else if($transaction->isFeeCredits() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::FEE_CREDITS;
        }
        else if($transaction->isPostpaid() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::POSTPAID;
        }

        return $rule;
    }

    public static function generateMoneyParamsForCapture(Payment\Entity $payment,Transaction\Entity  $transaction, $discount): array
    {
        $moneyParams = [];

        $amount = abs($transaction->getAmount());

        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

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

        $captureJournalEvents = new CaptureJournalEvents();

        $maxNegativeLimit = $captureJournalEvents->getMaxNegativeLimitForPaymentMerchantCapture($payment);

        $moneyParams[Constants::BASE_AMOUNT] = strval($amount);

        $creditLoadingPaymentInfo = $captureJournalEvents->extractCreditOrReserveBalanceLoadingPaymentInfo($payment);

        $transactionProcessor = (new Transaction\Processor\payment($transaction));

        if($creditLoadingPaymentInfo[Constants::IS_CREDIT_OR_RESERVE_BALANCE_LOADING_PAYMENT] === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        else if($transaction->isFeeCredits() === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $fee);
        }
        else if($transaction->isTypePayment() === true and $transaction->merchant !== null  and
            $transactionProcessor->featureFlagCheckForMerchantPostPaidCustomerFeeNotSettled($transaction->merchant))
        {
            $customerFee = $transaction->getCustomerFee();
            $customerGst = $transaction->getCustomerTax();

            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount - $customerFee - $customerGst);
            $moneyParams[Constants::TAX]                        = strval(abs($tax) + $customerGst);
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee) + $customerFee);
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
        }
        else if($transaction->isPostpaid() === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
        }
        else if ($transaction->isGratis() === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::RAZORPAY_REWARDS]           = strval($amount);
            $moneyParams[Constants::AMOUNT_CREDITS]             = strval($amount);
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
            else if($amount == 0)
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

            if ($maxNegativeLimit !== null)
            {
                $moneyParams[Constants::MERCHANT_BALANCE_LIMIT] = strval($maxNegativeLimit);
            }
        }

        return $moneyParams;
    }

    public static function generateMoneyParamsForCaptureDirectSettlement(Payment\Entity $payment,Transaction\Entity  $transaction): array
    {
        $moneyParams = [];

        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $moneyParams[Constants::BASE_AMOUNT] = strval($amount);

        if($transaction->isGratis() === true)
        {
            $moneyParams[Constants::RAZORPAY_REWARDS]           = strval($payment->getAmount());
            $moneyParams[Constants::AMOUNT_CREDITS]             = strval($payment->getAmount());
        }
        else if($transaction->isFeeCredits() === true)
        {
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $fee);
        }
        else if($transaction->isPostpaid() === true)
        {
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
        }
        //TODO: Check if we have to record this entries in CLS, since there are no money movement
        else if (($transaction->isGratis() === true) or
            ($payment->merchant->isFeatureEnabled(Feature\Constants::VAS_MERCHANT) === true) or
            ($payment->isHdfcVasDSCustomerFeeBearerSurcharge() === true))
        {
            if ($transaction->isFeeCredits() === true)
            {
                $moneyParams[Constants::FEE_CREDITS]                = strval(0);
            }
            else if ($transaction->isPostpaid() === true)
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
        // Normal merchant captured scenario (commissions considered)
        else
        {
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval( $fee + $tax);
        }

        return $moneyParams;
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

    private function getMaxNegativeLimitForPaymentMerchantCapture(Payment\Entity $payment)
    {
        if ((in_array($payment->getMethod() , self::NEGATIVE_BALANCE_ALLOWED_PAYMENT_METHODS) === false) or
            ($payment->isRecurringTypeInitial() === false))
        {
            return null;
        }

        $balance = $payment->merchant->getBalanceByTypeOrFail(Type::PRIMARY);

        $maxNegative = (new BalanceConfig\Core())->getMaxNegativeAmountAutoForBalanceId($balance->getId());

        if($maxNegative === 0)
        {
            $maxNegative = BalanceConfig\Entity::DEFAULT_MAX_NEGATIVE;
        }
        return $maxNegative;
    }
}
