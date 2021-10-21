<?php

namespace RZP\Models\Payment\Processor;

use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use Razorpay\Trace\Logger as Trace;

trait Transfer
{
    /**
     * Create a transfer payment entity and a corresponding transaction
     *
     * @param  array          $input
     * @param  Payment\Entity $originPayment
     *
     * @return Payment\Entity
     */
    public function processTransfer(array $input, Payment\Entity $originPayment = null) : Payment\Entity
    {
        $paymentData = $this->getTransferPaymentData($input, $originPayment);

        $payment = Tracer::inSpan(['name' => 'transfer.process.create_transfer_payment.create_payment'], function() use ($paymentData)
        {
            return $this->createPaymentEntity($paymentData);
        });

        $inputTrace = $input;

        unset($inputTrace['fta_data']['bank_account']['account_number'], $inputTrace['fta_data']['bank_account']['beneficiary_name']);

        $this->trace->info(TraceCode::PAYMENT_CREATED, ['payment_id' => $payment->getId(), 'input' => $inputTrace]);

        $this->setPaymentAttributes($payment);

        $this->processCurrencyConversionsForTransfer($originPayment, $payment);

        $txnCore = new Transaction\Core;

        list($txn, $feesSplit) = $txnCore->createFromPaymentTransferred($payment);

        $this->repo->saveOrFail($txn);

        $payment->setTax($txn->getTax());

        if ($this->merchant->isFeeBearerCustomer() === false)
        {
            //set and fee values from txn
            $payment->setFee($txn->getFee());
        }

        $txnCore->saveFeeDetails($txn, $feesSplit);

        return $payment;
    }

    protected function setPaymentAttributes(Payment\Entity $payment)
    {
        $payment->setStatus(Payment\Status::CAPTURED);

        $payment->setGatewayCaptured(true);

        $payment->setAuthorizeTimestamp();

        $payment->setCaptureTimestamp();

        $payment->setAttribute(Payment\Entity::CREATED_AT, time());
    }

    protected function getTransferPaymentData(array $input, $originPayment)
    {
        $paymentData = [
            Payment\Entity::AMOUNT          => $input['amount'],
            Payment\Entity::CONTACT         => $input['contact'] ?? null,
            Payment\Entity::EMAIL           => $input['email'] ?? null,
            Payment\Entity::CURRENCY        => $input['currency'],
            Payment\Entity::ON_HOLD         => $input['on_hold'] ?? 0,
            Payment\Entity::ON_HOLD_UNTIL   => $input['on_hold_until'] ?? null,
            Payment\Entity::METHOD          => Payment\Method::TRANSFER,
            Payment\Entity::NOTES           => $input['notes'],
        ];

        if ($originPayment !== null)
        {
            $paymentData[Payment\Entity::CONTACT] = $originPayment->getContact();

            $paymentData[Payment\Entity::EMAIL]   = $originPayment->getEmail();
        }

        return $paymentData;
    }

    /**
     * Set the base amount for the transfer payment
     * derived from the conversion rate applied to the
     * parent payment (if defined),
     * else converts for the transfer payment
     *
     * @todo: implementation pending
     *
     * @param  Payment\Entity|null  $originPayment
     * @param  Payment\Entity       $transferPayment
     */
    protected function processCurrencyConversionsForTransfer($originPayment, Payment\Entity $transferPayment)
    {
        if ($originPayment === null)
        {
            $this->processCurrencyConversions($transferPayment);
        }
        else if ($originPayment->getCurrency() === $transferPayment->getCurrency())
        {
            //
            // Note: This will need change for currencies other than INR. For the last
            // possible transfer on a payment, the transferBaseAmount generated may
            // not match the actual amount untransferred, caused by (floor) $amount
            //
            $conversionFactor = $originPayment->getCurrencyConversionRate();

            $transferBaseAmount = $transferPayment->getAmount() * $conversionFactor;

            $transferBaseAmount = (int) floor($transferBaseAmount);

            $transferPayment->setBaseAmount($transferBaseAmount);
        }
        else
        {
            // @todo: Different transfer currency
            //
            // Validate if 1. currency supported and 2. convert allowed for marketplace.
            //
            // If original payment date = today:
            // call processCurrencyConversions()
            //
            // else:
            // get historical rate on payment date, for transfer currency
            // calc and set baseAmount
        }
    }

}
