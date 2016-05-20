<?php

namespace Reconciliator\Base\SubReconciliator;

use Models\Payment;
use Models\Card\IIN;
use Models\Transaction;
use Models\Payment\Refund;

use Gateway\AxisMigs;

use Reconciliator\Orchestrator;

use Reconciliator\Base\Reconciliate as BaseReconciliate;


class RefundReconciliate
{
    /*******************
     * Instance objects
     *******************/

    protected $gatewayRepo;
    protected $paymentRepo;
    protected $refundRepo;
    protected $iinRepo;
    protected $transactionRepo;

    protected $payment;
    protected $refund;


    public function __construct()
    {
        // These are being used by the parent classes.
        $this->paymentRepo     = new Payment\Repository;
        $this->iinRepo         = new IIN\Repository;
        $this->gatewayRepo     = new AxisMigs\Repository;
        $this->transactionRepo = new Transaction\Repository;
        $this->refundRepo      = new Refund\Repository;
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
            $rowDetails = $this->getRowDetailsStructured($row);

            if (empty($rowDetails) === true)
            {
                continue;
            }

            try
            {
                // Validates that the payment status is not failed.
                $this->validatePaymentStatus();

                // Stores the gateway fees
                $this->recordGatewayFee($rowDetails[BaseReconciliate::GATEWAY_FEE]);

                // Stores the gateway service tax
                $this->recordGatewayServiceTax($rowDetails[BaseReconciliate::GATEWAY_SERVICE_TAX]);

                $this->setCardTypeIfAbsent($rowDetails[BaseReconciliate::CARD_TYPE]);

                $this->recordRrn();
            }
            catch (\Exception $ex)
            {
                // TODO: Raise a critical alert for not being able to perform one of the reconciliation actions.
                continue;
            }
        }
    }


    protected function getRowDetailsStructured($row)
    {
        // Gets refund ID
        $refundId = $this->getRefundId($row);

        // If refund id is not present, return. No point of evaluating the row.
        if (empty($refundId) === true)
        {
            return null;
        }

        try
        {
            $this->refund = $this->refundRepo->findOrFail($refundId);
        }
        catch (\Exception $ex)
        {
            // TODO: Raise an alert for not finding the refund in the db.
            return null;
        }

        // Sets the corresponding payment for the refund.
        $this->payment = $this->refund->payment;

        // If payment is not present, return. There's something wrong with this transaction.
        if (empty($this->payment) === true)
        {
            return null;
        }

        // Gets the card type details
        $cardType = $this->getCardType($row);

        // Gets the gateway service tax
        $serviceTax = $this->getServiceTax($row);

        // Gets the gateway fees
        $fee = $this->getFee($row);

        // Assign values to return
        $rowDetails = [
            BaseReconciliate::REFUND_ID           => $refundId,
            BaseReconciliate::CARD_TYPE           => $cardType,
            BaseReconciliate::GATEWAY_SERVICE_TAX => $serviceTax,
            BaseReconciliate::GATEWAY_FEE         => $fee,
        ];

        return $rowDetails;
    }


    protected function validatePaymentStatus()
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus === Payment\Status::FAILED)
        {
            // TODO: Raise a critical alert for payment status being failed.
        }
    }


    protected function setCardTypeIfAbsent($reconCardType)
    {
        if (empty($reconCardType) === true)
        {
            return;
        }

        $paymentIin = $this->payment->card->iinRelation;

        $iinCardType = $paymentIin->getType();

        if (empty($iinCardType) === true)
        {
            $paymentIin->setType($reconCardType);
            $this->iinRepo->saveOrFail($paymentIin);
        }
        else
        {
            if ($iinCardType !== $reconCardType)
            {
                // TODO: Raise a critical alert for mismatch of card types.
            }
        }
    }


    protected function recordRrn()
    {
        // TODO: Figure out what to do here.
    }


    protected function recordGatewayFee($reconGatewayFee)
    {
        if ($reconGatewayFee === null)
        {
            return;
        }

        $refundId = $this->refund->getId();

        $transaction = $this->transactionRepo->findByEntityId($refundId);

        if ($transaction === null)
        {
            // TODO: raise an alert about transaction being absent for an entity id.
        }

        $currentGatewayFee = $transaction->getGatewayFee();

        if ($currentGatewayFee === null)
        {
            $transaction->setGatewayFee($reconGatewayFee);
        }
        else
        {
            if ($currentGatewayFee !== $reconGatewayFee)
            {
                // TODO: raise an alert about stored service tax and recon service tax not being the same
            }
        }
    }


    protected function recordGatewayServiceTax($reconServiceTax)
    {
        if ($reconServiceTax === null)
        {
            return;
        }

        $refundId = $this->refund->getId();

        $transaction = $this->transactionRepo->findByEntityId($refundId);

        if ($transaction === null)
        {
            // TODO: raise an alert about transaction being absent for an entity id.
        }

        $currentGatewayServiceTax = $transaction->getGatewayServiceTax();

        if ($currentGatewayServiceTax === null)
        {
            $transaction->setGatewayServiceTax($reconServiceTax);
        }
        else
        {
            if ($currentGatewayServiceTax !== $reconServiceTax)
            {
                // TODO: raise an alert about stored service tax and recon service tax not being the same
            }
        }
    }
}