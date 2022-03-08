<?php

namespace RZP\Models\Ledger;

use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Constants\Entity as EntityConstant;

class CaptureJournalEvents
{
    public static function createTransactionMessageForMerchantCapture(Payment\Entity $payment, Transaction\Entity $transaction): array
    {
        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        $merchantCaptureData = array(
            Constants::TRANSACTOR_ID                 => $payment->getId(),
            Constants::TRANSACTOR_EVENT              => Constants::MERCHANT_CAPTURED,
            Constants::IDENTIFIERS                   => (object) [],
        );
        return array_merge($transactionMessage, $merchantCaptureData);
    }

    public static function createTransactionMessageForGatewayCapture(Payment\Entity $payment): array
    {
        $tax = $payment->getTax() != null ? $payment->getTax() : 0;
        $fee = $payment->getFee() != null ? $payment->getFee() - $tax : 0;

        $gateway = $payment->terminal ? $payment->terminal->getGateway() : "not found";

        return array(
            Constants::TRANSACTOR_ID                => $payment->getId(),
            Constants::API_TRANSACTION_ID           => $payment->getId(),
            Constants::MERCHANT_ID                  => $payment->getMerchantId(),
            Constants::CURRENCY                     => $payment->getCurrency(),
            Constants::AMOUNT                       => strval($payment->getAmount()),
            Constants::BASE_AMOUNT                  => strval($payment->getBaseAmount()),
            Constants::TAX                          => strval($tax),
            Constants::COMMISSION                   => strval($fee),
            Constants::TRANSACTOR_EVENT             => Constants::GATEWAY_CAPTURED,
            Constants::TRANSACTION_DATE             => $payment->getCreatedAt(),
            Constants::NOTES                        => new \stdClass(),
            Constants::IDENTIFIERS                  => (object) [
                Constants::GATEWAY        => $gateway,
            ],
        );
    }

    public static function fetchRulesForPaymentCredits(Transaction\Entity $transaction): array
    {
        $rule = [
        ];

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
