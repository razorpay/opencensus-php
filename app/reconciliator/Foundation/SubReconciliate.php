<?php

namespace Reconciliator\Foundation;


use Reconciliator\Orchestrator;
use Models\Payment;

class SubReconciliate
{
    /*************************
     * Internal Header Names
     *************************/

    const PAYMENT_ID  = 'payment_id';
    const CARD_TYPE   = 'card_type';
    const SERVICE_TAX = 'service_tax';


    public function __construct()
    {

    }


    /**
     * This is the start of the actual reconciliation.
     * Reconciliation is done for each row in the file content.
     * Validates payment status.
     * Records gateway fees.
     * Records gateway service tax.
     * Sets card type (debit/credit).
     * Records rrn.
     *
     * @param array $fileContents
     */
    public function startReconciliation($fileContents)
    {
        //$extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            // Every gateway has its own headers which have different meanings.
            // Hence, this is present in the gateway sub reconciliate class.
            $rowDetails = $this->getRowDetailsStructured($row);

            if (empty($rowDetails) === true)
            {
                continue;
            }

            // Validates that the payment status is not failed.
            $this->validatePaymentStatus();

            // Stores the gateway fees
            $this->recordGatewayFees();

            // Stores the gateway service tax
            $this->recordGatewayServiceTax();

            $this->setCardTypeIfAbsent($rowDetails);

            $this->recordRrn();
        }
    }

    protected function getRowDetailsStructured($row)
    {
        // Gets payment ID
        $paymentId = $this->getPaymentId($row);

        // If payment id is not present, return. No point of evaluating the row.
        if (empty($paymentId) === true)
        {
            return null;
        }

        try
        {
            $this->payment = $this->paymentRepo->findOrFail($paymentId);
        }
        catch (\Exception $ex)
        {
            // TODO: Raise an alert for not finding the payment in the db.
            return null;
        }

        // Gets the card type details
        $cardType = $this->getCardType($row);

        // Gets the service tax
        $serviceTax = $this->getServiceTax($row);

        // Assign values to return
        $rowDetails = [
            self::PAYMENT_ID  => $paymentId,
            self::CARD_TYPE   => $cardType,
            self::SERVICE_TAX => $serviceTax,
        ];

        return $rowDetails;
    }


    protected function validatePaymentStatus()
    {
        $paymentStatus = $this->payment->getStatus();

        //$this->getAttribute(self::STATUS) === Status::FAILED

        if ($paymentStatus === Payment\Status::FAILED)
        {
            // TODO: Raise a critical alert for payment status being failed.
        }
    }


    protected function setCardTypeIfAbsent($rowDetails)
    {
        if (empty($rowDetails[self::CARD_TYPE]) === true)
        {
            return;
        }

        $paymentIin = $this->payment->card->iinRelation;

        $iinCardType = $paymentIin->getType();

        if (empty($iinCardType) === true)
        {
            $paymentIin->setType($rowDetails[self::CARD_TYPE]);
            $this->iinRepo->saveOrFail($paymentIin);
        }
        else
        {
            if ($iinCardType !== $rowDetails[self::CARD_TYPE])
            {
                // TODO: Raise a critical alert for mismatch of card types.
            }
        }
    }


    protected function recordRrn()
    {

    }


    protected function recordGatewayFees()
    {
        // TODO: Store in payments table
    }


    protected function recordGatewayServiceTax()
    {
        // TODO: Store in payments table
    }
}