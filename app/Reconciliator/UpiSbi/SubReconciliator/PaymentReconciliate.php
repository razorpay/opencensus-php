<?php

namespace RZP\Reconciliator\UpiSbi\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Upi\Sbi\Action;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /**
     * @see https://drive.google.com/drive/u/0/folders/0B1kf6HOmx7JBTmMzTXgwQVRrNm8
     */

    const ORDER_NUMBER          = 'order_no';
    const TRANS_REF_NUMBER      = 'trans_ref_no';
    const TRANSACTION_STATUS    = 'transaction_status';
    const TRANSACTION_AMOUNT    = 'transaction_amount';
    const PAYER_VIRTUAL_ACCOUNT = 'payer_virtual_account';
    const PAYER_VIRTUAL_ADDRESS = 'payer_virtual_address';
    const PAYEE_VIRTUAL_ACCOUNT = 'payee_virtual_account';
    const PAYER_ACCOUNT_NAME    = 'payer_ac_name';
    const CUSTOMER_REF_NO       = 'customer_ref_no';

    const BLACKLISTED_COLUMNS = [
        self::PAYER_VIRTUAL_ACCOUNT,
        self::PAYER_ACCOUNT_NAME,
    ];

    const PII_COLUMNS = [
        'payer_ac_no',
        'payer_virtual_address',
        'device_type',
        'app',
        'device_os',
        'device_mobile_no',
        'device_location',
        'ip_address',
    ];

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::ORDER_NUMBER] ?? null;

        $reconStatus = $this->getReconPaymentStatus($row);

        if ($reconStatus === Status::FAILED)
        {
            $this->setFailUnprocessedRow(false);

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::MIS_FILE_PAYMENT_FAILED ,
                    'payment_id' => $paymentId,
                    'gateway'    => $this->gateway
                ]);

            return null;
        }

        return $paymentId;
    }

    private function getReconVpa($row)
    {
        return $row[self::PAYER_VIRTUAL_ADDRESS] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::CUSTOMER_REF_NO] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = strtolower($row[self::TRANSACTION_STATUS]) ?? null;

        return Status::getPaymentStatus($status);
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        $paymentAmount = floatval($row[self::TRANSACTION_AMOUNT]) * 100;

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Converting to string using number_format and then converting
        // is a hack to avoid this issue
        return intval(number_format($paymentAmount, 2, '.', ''));
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->upi->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setNpciReferenceId($referenceNumber);
    }

    /**
     * This returns the array of attributes to be saved while force authorizing the payment.
     *
     * @param $row
     * @return array
     */
    protected function getInputForForceAuthorize($row)
    {
        return [
            Base\Reconciliate::REFERENCE_NUMBER => $this->getReferenceNumber($row),
            'acquirer' => [
                Payment\Entity::VPA         => $this->getReconVpa($row),
                Payment\Entity::REFERENCE16 => $this->payment->getReference16()
            ]
        ];
    }
}
