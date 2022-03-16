<?php

namespace RZP\Models\Ledger;

use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Constants\Entity as EntityConstant;

class CaptureJournalEvents
{
    public static function createTransactionMessageForMerchantCapture(Payment\Entity $payment, Transaction\Entity $transaction, bool $isTransactionPresent): array
    {
        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        //If the transaction was already present at gateway capture stage then we don't send the same api transaction id in merchant captured stage
        if($isTransactionPresent === true)
        {
            unset($transactionMessage[Constants::API_TRANSACTION_ID]);
        }

        $merchantCaptureData = array(
            Constants::TRANSACTOR_ID                 => $payment->getPublicId(),
            Constants::TRANSACTOR_EVENT              => Constants::MERCHANT_CAPTURED,
        );
        return array_merge($transactionMessage, $merchantCaptureData);
    }

    public static function createTransactionMessageForGatewayCapture(Payment\Entity $payment): array
    {
        $tax = $payment->getTax() != null ? $payment->getTax() : 0;
        $fee = $payment->getFee() != null ? $payment->getFee() - $tax : 0;

        $gateway = $payment->terminal ? $payment->terminal->getGateway() : "not found";

        // api transaction id is assigned to transaction id if it is present in payment entity else payment id is passed as api transaction id
        return array(
            Constants::TRANSACTOR_ID                => $payment->getPublicId(),
            Constants::API_TRANSACTION_ID           => ($payment->getTransactionId() !== null) ? $payment->getTransactionId() : $payment->getId(),
            Constants::MERCHANT_ID                  => $payment->getMerchantId(),
            Constants::CURRENCY                     => $payment->getCurrency(),
            Constants::AMOUNT                       => strval($payment->getAmount()),
            Constants::BASE_AMOUNT                  => strval($payment->getBaseAmount()),
            Constants::TAX                          => strval($tax),
            Constants::COMMISSION                   => strval($fee),
            Constants::TRANSACTOR_EVENT             => Constants::GATEWAY_CAPTURED,
            Constants::TRANSACTION_DATE             => $payment->getCreatedAt(),
            Constants::IDENTIFIERS                  => [
                Constants::GATEWAY          => $gateway,
            ],
        );
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
}
