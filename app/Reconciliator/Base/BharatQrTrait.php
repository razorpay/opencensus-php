<?php

namespace RZP\Reconciliator\Base;

use RZP\Trace\TraceCode;

trait BharatQrTrait
{
    /**
     * In case of normal payments merchant reference is payment id,
     * but in case of bharat qr payments qr_code_id is used as merchant
     * reference. So when payment is fetch using the merchant reference it will be null,
     * In that case we will search the bharat qr entity with that merchant reference,
     * we fetch  the payment id from that bharat qr entity and return it
     *
     * @param string $bankReference
     * @param array  $row
     *
     * @return null|string
     */
    protected function getPaymentIdFromBharatQr(string $bankReference, array $row, int $amount)
    {
        $bharatQr = $this->repo->bharat_qr->findByProviderReferenceIdAndAmount($bankReference, $amount);

        if ($bharatQr === null)
        {
            $this->alertUnexpectedBharatQrPayment($bankReference, $row);

            //
            // We don't want to fail entire reconciliation for
            // bharat qr payments. There can be missed notification.
            // So we will notify the payment in slack and mark the row
            // as successful.
            //
            $this->setFailUnprocessedRow(false);

            return null;
        }

        return $bharatQr->payment->getId();
    }

    protected function alertUnexpectedBharatQrPayment(string $merchantReference, array $row)
    {
        $this->messenger->raiseReconAlert(
            [
                'trace_code'     => TraceCode::BHARAT_QR_UNEXPECTED_PAYMENT,
                'info_code'      => InfoCode::PAYMENT_ABSENT,
                'message'        => 'Unexpected Bharat Qr Payment',
                'bank_reference' => $merchantReference,
                'gateway'        => $this->gateway
            ]);
    }
}
