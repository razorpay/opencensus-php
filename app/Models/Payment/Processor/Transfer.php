<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Transaction;

trait Transfer
{
    /**
     * Create a payment entity for marketplace transfer,
     * create and process a payment transaction
     *
     * @param  array           $input
     * @param  Payment\Entity  $originPayment
     */
    public function processTransfer(array $input, Payment\Entity $originPayment = null) : Payment\Entity
    {
        $paymentData = [
            Payment\Entity::AMOUNT      => $input['amount'],
            Payment\Entity::CONTACT     => $input['contact'] ?? null,
            Payment\Entity::EMAIL       => $input['email'] ?? null,
            Payment\Entity::CURRENCY    => $input['currency'],
            Payment\Entity::ON_HOLD     => $input['on_hold'] ?? 0,
            Payment\Entity::HOLD_UNTIL  => $input['hold_until'] ?? null,
            Payment\Entity::METHOD      => Payment\Method::TRANSFER,
        ];

        $payment = $this->createPaymentEntity($paymentData);

        $this->processCurrencyConversionsForTransfer($originPayment, $payment);

        $payment->setStatus(Payment\Status::CAPTURED);

        $payment->setMarketplaceGateway();

        $payment->setGatewayCaptured(true);

        $payment->setAuthorizeTimestamp();

        $payment->setCaptureTimestamp();

        list($txn, $feesSplit) = (new Transaction\Core)->createFromPaymentTransferred($payment);

        $this->repo->saveOrFail($txn);

        $this->saveFeeDetails($txn, $feesSplit);

        return $payment;
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
    protected function processCurrencyConversionsForTransfer(Payment\Entity $originPayment, Payment\Entity $transferPayment)
    {
        if ($originPayment === null)
        {
            $this->processCurrencyConversions($transferPayment);
        }
        else if ($originPayment->getCurrency() === $transferPayment->getCurrency())
        {
            $conversionFactor = $originPayment->getCurrencyConversionRate();

            $transferBaseAmount = $transferPayment->getAmount() * $conversionFactor;

            $transferPayment->setBaseAmount(floor($transferBaseAmount));
        }
        else
        {
            // different transfer currency
            //
            // Validate currency supported and convert allowed for marketplace.
            //
            // If orignial payment date = today:
            // call processCurrencyConversions()
            //
            // else:
            // get historical rate on payment date, for transfer currency
            // set baseAmount
        }
    }

}
