<?php

namespace RZP\Models\Ledger;

use App;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Ledger\Constants as LedgerConstants;

class CaptureJournalEvents
{
    public static function createTransactionMessageForMerchantCapture(Payment\Entity $payment, Transaction\Entity $transaction): array
    {
        if ($payment->isDirectSettlement() === true)
        {
            $moneyParams = self::generateMoneyParamsForCaptureDirectSettlement($transaction);

            $additionalParams = self::fetchRulesForPaymentCreditsDS($transaction);
        }
        else
        {
            $moneyParams = self::generateMoneyParamsForCapture($transaction);

            $additionalParams = self::fetchRulesForPaymentCredits($transaction);
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

        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $gateway = $payment->terminal ? $payment->terminal->getGateway() : "not found";

        // api transaction id is assigned to transaction id if it is present in payment entity else payment id is passed as api transaction id
        $message = array(
            Constants::TRANSACTOR_ID                => $payment->getPublicId(),
            Constants::MERCHANT_ID                  => $payment->getMerchantId(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::TRANSACTOR_EVENT             => Constants::GATEWAY_CAPTURED,
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

    public static function fetchRulesForPaymentCredits(Transaction\Entity $transaction)
    {
        $rule = null;

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
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS;
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
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS;
        }

        return $rule;
    }

    public static function generateMoneyParamsForCapture(Transaction\Entity  $transaction): array
    {
        $moneyParams = [];

        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $moneyParams[Constants::BASE_AMOUNT] = strval($amount);

        if($transaction->isFeeCredits() === true)
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $fee);
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
        }

        return $moneyParams;
    }

    public static function generateMoneyParamsForCaptureDirectSettlement(Transaction\Entity  $transaction): array
    {
        $moneyParams = [];

        $amount = abs($transaction->getAmount());
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        $moneyParams[Constants::BASE_AMOUNT] = strval($amount);

        if($transaction->isFeeCredits() === true)
        {
            $moneyParams[Constants::DS_GMV_AMOUNT]              = strval($amount);
            $moneyParams[Constants::DS_CONTROL_AMOUNT]          = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $fee);
        }
        else if($transaction->isPostpaid() === true)
        {
            $moneyParams[Constants::DS_GMV_AMOUNT]              = strval($amount);
            $moneyParams[Constants::DS_CONTROL_AMOUNT]          = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
        }
        else if ($transaction->isGratis() === true)
        {
            $moneyParams[Constants::DS_GMV_AMOUNT]              = strval($amount);
            $moneyParams[Constants::DS_CONTROL_AMOUNT]          = strval($amount);
        }
        // Normal merchant captured scenario (commissions considered)
        else
        {
            $moneyParams[Constants::DS_GMV_AMOUNT]              = strval($amount);
            $moneyParams[Constants::DS_CONTROL_AMOUNT]          = strval($amount);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval( $fee + $tax);
        }

        return $moneyParams;
    }
}
