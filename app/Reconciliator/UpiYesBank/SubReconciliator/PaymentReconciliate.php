<?php

namespace RZP\Reconciliator\UpiYesBank\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Upi\Sbi\Action;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    use Base\UpiReconTrait;

    const PG_MERCHANT_ID        = 'pg_merchant_id';
    const ORDER_NUMBER          = 'order_no';
    const TRANS_REF_NUMBER      = 'trans_ref_no';
    const TRANSACTION_STATUS    = 'transaction_status';
    const COLUMN_PAYMENT_AMOUNT = 'transaction_amount';
    const PAYER_VIRTUAL_ACCOUNT = 'payer_virtual_account';
    const PAYER_VIRTUAL_ADDRESS = 'payer_virtual_address';
    const PAYEE_VIRTUAL_ADDRESS = 'payee_virtual_address';
    const PAYEE_VIRTUAL_ACCOUNT = 'payee_virtual_account';
    const PAYER_ACCOUNT_NAME    = 'payer_ac_name';
    const PAYER_ACCOUNT_NO      = 'payer_ac_no';
    const CUSTOMER_REF_NO       = 'customer_ref_no';
    const PAYER_IFSC_CODE       = 'payer_ifsc_code';

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

    // todo: Fill in these fields when we add unexpected flows
    const CALL_BACK_FIELD_MAPPING = [];

    protected $gatewayName  = Gateway::UPI_YESBANK;

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

        return $this->getPaymentIdFromUpi($row);
    }

    /**
     * Fetch upi entity from upi using npci_reference_id
     * and payment and gateway
     *
     * @param array $row
     * @return |null
     */
    protected function getPaymentIdFromUpi(array $row)
    {
        $upiEntity = null;

        $referenceNumber = $this->getReferenceNumber($row);

        $paymentId = $row[self::ORDER_NUMBER] ?? null;

        if ((empty($referenceNumber) === true) or
            (empty($paymentId) === true))
        {
            // Dont have enough info to get the payment ID,
            // so trace it and return null
            $this->trace->info(TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'            => Base\InfoCode::RECON_MISSING_COLUMN_VALUE,
                    'payment_reference_id' => $referenceNumber,
                    'payment_id'           => $paymentId,
                    'gateway'              => $this->gateway,
                    'batch_id'             => $this->batchId
                ]);

            return null;
        }

        $upiEntity = $this->getUpiExpectedEntity($paymentId, $row);

        if ($upiEntity === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'           => TraceCode::RECON_MISMATCH,
                    'info_code'            => Base\InfoCode::PAYMENT_ABSENT,
                    'payment_reference_id' => $referenceNumber,
                    'payment_id'           => $paymentId,
                    'gateway'              => $this->gateway,
                    'batch_id'             => $this->batchId
                ]);

            return $paymentId;
        }

        // Also now since we have found/created a new UPI Entity we will consider
        // this to be the gateway payment id
        $this->gatewayPayment = $upiEntity;

        return $upiEntity->getPaymentId();
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

    protected function getAccountDetails($row)
    {
        $accountDetails = [];

        if (empty($row[self::PAYER_VIRTUAL_ADDRESS]) === false)
        {
            $accountDetails['vpa'] = $row[self::PAYER_VIRTUAL_ADDRESS];
        }

        if (empty($row[self::PAYER_IFSC_CODE]) === false)
        {
            $accountDetails['ifsc'] = $row[self::PAYER_IFSC_CODE];
        }

        if (empty($row[self::PAYER_ACCOUNT_NAME]) === false)
        {
            $accountDetails['name'] = $row[self::PAYER_ACCOUNT_NAME];
        }

        return $accountDetails;
    }

    /**
     * Add provider data with recon VPA if the payerVPA is missing. We will log if the payervpa from the gateway does
     * does not match reconVPA
     * @param $row
     * @return array
     */
    protected function persistAccountDetails(array $rowDetails, PublicEntity $gatewayPayment)
    {
        $payerVpa = $gatewayPayment->getVpa();

        $reconVpa = $rowDetails[Reconciliate::ACCOUNT_DETAILS]['vpa'] ?? null;

        if (($payerVpa === null) and
            (empty($reconVpa) === false))
        {
            $gatewayPayment->fill($rowDetails[Reconciliate::ACCOUNT_DETAILS]);

            $gatewayPayment->generatePspData($rowDetails);

            return;
        }

        if (strtolower($payerVpa) !== strtolower($reconVpa))
        {
            $this->trace->info(TraceCode::RECON_INFO_ALERT, [
                'message'           => 'Payer VPA is not same as in recon',
                'info_code'         => Base\InfoCode::VPA_MISMATCH,
                'payment_id'        => $this->payment->getId(),
                'payment_status'    => $this->payment->getStatus(),
                'api_vpa'           => $payerVpa,
                'recon_vpa'         => $reconVpa,
                'gateway'           => $this->gateway
            ]);
        }
    }

    protected function generateCallbackData(array $row)
    {
        $callbackData = [];

        foreach (self::CALL_BACK_FIELD_MAPPING as $callbackField => $reconColumn)
        {
            if (empty($row[$reconColumn]) === true)
            {
                // Required data missing
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'info_code'    => Base\InfoCode::RECON_INSUFFICIENT_DATA_FOR_ENTITY_CREATION,
                        'message'      => 'Data missing to create Payment via Recon',
                        'rrn'          => $this->getReferenceNumber($row),
                        'empty_column' => $reconColumn,
                        'gateway'      => $this->gateway,
                        'batch_id'     => $this->batchId,
                    ]
                );

                return null;
            }

            $callbackData[$callbackField] = $row[$reconColumn];
        }

        // Todo: for the fields we are not getting the data in MIS directly, add the data accordingly

        return $callbackData;
    }
}
