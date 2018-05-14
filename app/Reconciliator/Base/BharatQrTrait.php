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
    protected function getPaymentIdFromBharatQr(string $bankReference, array $row)
    {
        $bharatQr = $this->repo->bharat_qr->findByProviderReferenceId($bankReference);

        if ($bharatQr === null)
        {
            $this->alertUnexpectedBharatQrPayment($bankReference, $row);

            return null;
        }

        return $bharatQr->payment->getId();
    }

    protected function alertUnexpectedBharatQrPayment(string $merchantReference, array $row)
    {
        $this->messenger->raiseReconAlert(
            [
                'trace_code'     => TraceCode::BHARAT_QR_UNEXPECTED_PAYMENT,
                'info_code'      => 'PAYMENT_ABSENT',
                'message'        => 'Unexpected Bharat Qr Payment',
                'bank_reference' => $merchantReference,
                'row'            => $row,
                'gateway'        => $this->gateway
            ]);
    }
}
