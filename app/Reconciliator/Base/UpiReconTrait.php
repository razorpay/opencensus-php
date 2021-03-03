<?php

namespace RZP\Reconciliator\Base;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Base\Repository;

/**
 * Trait UpiReconciliateTrait
 * @package RZP\Reconciliator\Base
 *
 * @property Payment\Entity $payment
 */
trait UpiReconTrait
{
    /**
     * Method will return expected payment for given paymentId, rrn.
     * Note: Later it can also handle the amount mismatches
     *
     * @param string $paymentId
     * @param string $rrn
     */
    protected function getUpiExpectedEntity(string $paymentId, array $row)
    {
        $upi = $this->getUpiAuthorizeEntityByPaymentId($paymentId);

        // If the UPI entity is not present in database, this may be an unexpected payment
        // Later checks for re-validating the payment will have this handled.
        if (($upi instanceof Entity) === false)
        {
            return null;
        }

        if ($upi->getGateway() !== $this->gatewayName)
        {
            // TODO: Raise a critical alert
            return null;
        }

        // If the existing payment is not already reconciled, we will return the same entity
        if (empty($upi->getReconciledAt()) === true)
        {
            return $upi;
        }

        // Formatting the npci reference id just to make sure any accidental trimming
        $existingRrn = $upi->getNpciReferenceId();

        // Even though the payment is already reconciled and if we do not have saved
        // a valid RRN, we will not go ahead with the reconciliation.
        if ($this->isUpiValidRrn($existingRrn) === false)
        {
            // TODO: Raise a critical alert
            return null;
        }

        $reconRrn = $this->getReferenceNumber($row);

        // If both the RRNs are same, we can return the payment to be reconciled
        if ($existingRrn === $reconRrn)
        {
            return $upi;
        }

        // Since we are receiving extra attempt but the status is failed, we do not need to create the payment
        // The validatePaymentStatus will function will take care of this if we send the existing entity
        if ($this->getReconPaymentStatus($row) !== Payment\Status::AUTHORIZED)
        {
            return $upi;
        }

        // Now we have multiple credit scenario, where one credit is already reconciled
        // And the RRN for reconciled one is not same as recon row rrn.
        $callbackData = $this->generateCallbackData($row);

        $response = (new Payment\Service)->unexpectedCallback($callbackData, $reconRrn, $this->gatewayName);

        if (empty($response[Entity::PAYMENT_ID]) === true)
        {
            // TODO: Trace critical
            return null;
        }

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'infoCode'      => InfoCode::RECON_UNEXPECTED_PAYMENT_FOR_MULTIPLE_CREDIT,
                'payment_id'    => $response[Entity::PAYMENT_ID],
                'rrn'           => $reconRrn,
                'gateway'       => $this->gateway,
            ]);

        return $this->getUpiAuthorizeEntityByPaymentId($response[Entity::PAYMENT_ID]);
    }

    /**
     * @param string $paymentId
     * @return Entity
     */
    protected function getUpiAuthorizeEntityByPaymentId(string $paymentId)
    {
        return $this->getUpiRepo()->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    /**
     * @return Repository
     */
    protected function getUpiRepo(): Repository
    {
        return $this->repo->upi;
    }

    /**
     * Sometimes the Rrn we receive in MIS, is either more than 12 digits, with
     * extra 0s appended at the left of it, or they are already trimmed so the 0s are
     * removed from left part of string, making it unsearchable in the Upi repo.
     *
     * @param $rrn
     */
    protected function toUpiRrn($rrn)
    {
        // First convert the RRN to string
        $string = (string) $rrn;

        // Now check if RRN has non empty value
        if(empty(trim($rrn)) === true)
        {
            // Return the original rrn
            return $rrn;
        }

        $rrn = ltrim($rrn, '0');

        $rrn = str_pad($rrn, 12, '0', STR_PAD_LEFT);

        return $rrn;
    }

    /**
     * An RRN has to be 12 digit long string, this method will only remove most typical cases.
     * @param $rrn
     * @return bool
     */
    protected function isUpiValidRrn($rrn)
    {
        return ((strlen($rrn) === 12) and
                (is_numeric($rrn) === true));
    }

    /**
     * Method will validate the recon amount with payment base amount. It will also allow amount
     * within a margin, This margin value needs to provided by corresponding gateway.
     * NOTE: Gateways need to define a method for margin called `getAmountMarginAllowed`.
     *
     * @param array $row
     * @return bool
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        $baseAmount     = $this->payment->getBaseAmount();
        $reconAmount    = $this->getReconPaymentAmount($row);

        // If the amount is same, return true
        if ($baseAmount === $reconAmount)
        {
            return true;
        }

        $margin     = $this->getAmountMarginAllowed($row);

        $minAllowed = $this->payment->getBaseAmount() - $margin;
        $maxAllowed = $this->payment->getBaseAmount() + $margin;

        $allowed    = (($reconAmount >= $minAllowed) and
                       ($reconAmount <= $maxAllowed));

        $metaId = null;

        if ($allowed === true)
        {
            $metaId = (new Payment\PaymentMeta\Core)->addGatewayAmountInformation($this->payment, $reconAmount);
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code'      => TraceCode::RECON_INFO_ALERT,
                'info_code'       => InfoCode::AMOUNT_MISMATCH,
                'payment_id'      => $this->payment->getId(),
                'expected_amount' => $baseAmount,
                'recon_amount'    => $reconAmount,
                'currency'        => $this->payment->getCurrency(),
                'gateway'         => $this->gateway,
                'margin'          => $margin,
                'allowed'         => $allowed,
                'payment_meta_id' => $metaId,
            ]);

        return $allowed;
    }
}
