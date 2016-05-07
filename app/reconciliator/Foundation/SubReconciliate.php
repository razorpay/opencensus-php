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


    public function startReconciliation($fileContents)
    {
        //$extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
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


    protected function validatePaymentStatus()
    {
        $paymentStatus = $this->payment->getStatus();

        //$this->getAttribute(self::STATUS) === Status::FAILED

        if ($paymentStatus === Payment\Status::FAILED)
        {
            // TODO: Throw an exception for status being failed.
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
                // TODO: Raise an alert for mismatch of card types.
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