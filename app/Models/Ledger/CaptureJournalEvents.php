<?php

namespace RZP\Models\Ledger;

use App;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Constants\Entity as EntityConstant;
use RZP\Trace\TraceCode;

class CaptureJournalEvents
{

    //TODO::Figure out how to handle amount credits usecase
    public static function createTransactionMessageForMerchantCapture(Payment\Entity $payment, Transaction\Entity $transaction, bool $isTransactionPresent): array
    {
        $moneyParams = self::generateMoneyParamsForCapture($transaction);
        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        //If the transaction was already present at gateway capture stage then we don't send the same api transaction id in merchant captured stage
        if($isTransactionPresent === true)
        {
            unset($transactionMessage[Constants::API_TRANSACTION_ID]);
        }

        $merchantCaptureData = array(
            Constants::TRANSACTOR_ID                 => $payment->getPublicId(),
            Constants::TRANSACTOR_EVENT              => Constants::MERCHANT_CAPTURED,
            Constants::MONEY_PARAMS                  => $moneyParams,
        );
        return array_merge($transactionMessage, $merchantCaptureData);
    }

    public static function createTransactionMessageForGatewayCapture(Payment\Entity $payment): array
    {
        $app = App::getFacadeRoot();
        $trace = $app['trace'];

        $gateway = $payment->terminal ? $payment->terminal->getGateway() : "not found";

        // api transaction id is assigned to transaction id if it is present in payment entity else payment id is passed as api transaction id
        $message = array(
            Constants::TRANSACTOR_ID                => $payment->getPublicId(),
            Constants::MERCHANT_ID                  => $payment->getMerchantId(),
            Constants::CURRENCY                     => $payment->getCurrency(),
            Constants::TRANSACTOR_EVENT             => Constants::GATEWAY_CAPTURED,
            Constants::TRANSACTION_DATE             => $payment->getCreatedAt(),
            Constants::IDENTIFIERS                  => [
                Constants::GATEWAY          => $gateway,
            ],
            Constants::MONEY_PARAMS                 => [
                Constants::AMOUNT           => strval($payment->getAmount()),
                Constants::BASE_AMOUNT      => strval($payment->getBaseAmount()),
            ]
        );

        if($payment->getTransactionId() !== null)
        {
            $message[Constants::API_TRANSACTION_ID] = $payment->getTransactionId();
        }
        else
        {
            $trace->info(
                TraceCode::TRANSACTION_ID_UNAVAILABLE_AT_GATEWAY_CAPTURE,
                [
                    'payment_id'        => $payment->getId(),
                ]);
        }

        return $message;
    }

    public static function fetchRulesForPaymentCredits(Transaction\Entity $transaction)
    {
        $rule = null;

        if($transaction->isGratis() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS;
        }

        if($transaction->isFeeCredits() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::FEE_CREDITS;
        }

        if($transaction->isPostpaid() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::POSTPAID;
        }

        if(($transaction->source->getEntityName() === EntityConstant::PAYMENT) and
            ($transaction->source->isDirectSettlement() === true))
        {
            $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT;
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
        else if($transaction->isPostpaid())
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        // Normal merchant captured scenario (commissions considered)
        else
        {
            $moneyParams[Constants::GMV_AMOUNT]                 = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount - $fee - $tax);
            $moneyParams[Constants::TAX]                        = strval(abs($tax));
            $moneyParams[Constants::COMMISSION]                 = strval(abs($fee));
        }

        return $moneyParams;
    }
}
