<?php

namespace Reconciliator\Axis\SubReconciliator;


use Models\Card\IIN;
use Reconciliator\FileProcessor;
use Reconciliator\Orchestrator;

use Models\Payment;
use Gateway\AxisMigs;

class Refund
{
    const CREDIT = 'credit';
    const DEBIT = 'debit';

    /*******************
     * Row Header Names
     *******************/

    const ROW_PAYMENT_ID = 'merchant_trans_ref';
    const ROW_CARD_TYPE  = 'card_type';

    /*************************
     * Internal Header Names
     *************************/

    const PAYMENT_ID = 'payment_id';
    const CARD_TYPE  = 'card_type';

    /*******************
     * Instance objects
     *******************/

    protected $paymentRepo;
    protected $gatewayRepo;
    protected $iinRepo;

    /*********************
     * Instance variables
     *********************/

    protected $payment;

    public function __construct()
    {
        $this->paymentRepo = new Payment\Repository;
        $this->gatewayRepo = new AxisMigs\Repository;
        $this->iinRepo     = new IIN\Repository;
    }


    public function startReconciliation($fileContents)
    {
        // TODO: While reading the file contents, exclude file_details and sheet_name params.
        //$extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            // TODO: Consider setting rowDetails to instance.
            $rowDetails = $this->getRowDetailsStructured($row);

            if (empty($rowDetails) === true)
            {
                continue;
            }

            // Validates that the payment status is not failed.
            // TODO: Instead validate that it's refunded?
            $this->validatePaymentStatus();

            // Stores the gateway fees
            $this->recordGatewayFees();

            // Stores the gateway service tax
            $this->recordGatewayServiceTax();

            $this->setCardTypeIfAbsent($rowDetails);
        }
    }


    protected function getRowDetailsStructured($row)
    {
        $paymentId = $row[self::ROW_PAYMENT_ID];

        // If payment id is not present, return. No point of evaluating the row.
        if (empty($paymentId) === true)
        {
            return null;
        }

        $this->payment = $this->paymentRepo->findOrFail($paymentId);

        $cardType = strtolower($row[self::ROW_CARD_TYPE]);

        if ($cardType === 'c')
        {
            $cardType = self::CREDIT;
        }
        else if ($cardType === 'd')
        {
            $cardType = self::DEBIT;
        }
        else
        {
            $cardType = null;
        }

        $rowDetails = [
            self::PAYMENT_ID => $paymentId,
            self::CARD_TYPE  => $cardType,
        ];

        return $rowDetails;
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


    protected function recordGatewayFees()
    {
        // TODO: Store in payments table
    }


    protected function recordGatewayServiceTax()
    {
        // TODO: Store in payments table
    }


    protected function setCardTypeIfAbsent($rowDetails)
    {
        if (empty($rowDetails[self::ROW_CARD_TYPE]) === true)
        {
            return;
        }
        
        $paymentIin = $this->payment->card->iinRelation;

        $iinCardType = $paymentIin->getType();

        if (empty($iinCardType) === true)
        {
            $paymentIin->setType($rowDetails[self::ROW_CARD_TYPE]);
            $this->iinRepo->saveOrFail($paymentIin);
        }
        else
        {
            if ($iinCardType !== $rowDetails[self::ROW_CARD_TYPE])
            {
                // TODO: Raise an alert for mismatch of card types.
            }
        }
    }
}