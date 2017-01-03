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
     * @param  Payment\Entity  $originPayment
     * @param  array           $input
     */
    public function processTransfer(Payment\Entity $originPayment, array $input) : Payment\Entity
    {
        $input['method'] = Payment\Method::TRANSFER;

        $payment = $this->createPaymentEntity($input);

        $this->processCurrencyConversionsForTransfer($originPayment, $payment);

        $payment->setStatus(Payment\Status::CAPTURED);

        $payment->setMarketplaceGateway();

        $payment->setGatewayCaptured(true);

        $payment->setAuthorizeTimestamp();

        $payment->setCaptureTimestamp();

        list($txn, $feesSplit) = (new Transaction\Core)->createFromPaymentTransferred($payment);

        $this->repo->saveOrFail($txn);

        $payment->originPayment()->associate($originPayment);

        $this->repo->saveOrFail($payment);

        $this->saveFeeDetails($txn, $feesSplit);

        return $payment;
    }

    /**
     * Set the base amount for the transfer payment
     * derived from the conversion rate applied to the
     * parent payment
     *
     * @param  Payment\Entity $originPayment
     * @param  Payment\Entity $transferPayment
     */
    protected function processCurrencyConversionsForTransfer(Payment\Entity $originPayment, Payment\Entity $transferPayment)
    {
        if ($originPayment->getCurrency() === $transferPayment->getCurrency())
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
