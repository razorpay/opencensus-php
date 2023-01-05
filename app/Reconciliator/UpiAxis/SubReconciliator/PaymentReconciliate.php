<?php

namespace RZP\Reconciliator\UpiAxis\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Upi\Axis\Fields;
use RZP\Gateway\Upi\Axis\Action;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\Reconciliate;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Reconciliator\Base\SubReconciliator\Upi\UpiPaymentServiceReconciliate;

class PaymentReconciliate extends UpiPaymentServiceReconciliate
{
    const RRN                     = 'rrn';
    const VPA                     = 'vpa';
    const IFSC                    = 'ifsc';
    const TXN_ID                  = 'txnid';
    const COLUMN_PAYMENT_AMOUNT   = 'amount';
    const RESPCODE                = 'respcode';
    const RESPONSE                = 'response';
    const CREDITVPA               = 'creditvpa';
    const ACCOUNT_CUST_NAME       = 'account_cust_name';
    const COLUMN_PAYMENT_ID       = ['order_id', 'orderid'];
    const COLUMN_TRANSACTION_DATE = ['transaction_date', 'txn_date'];
    const COLUMN_MOBILE_NO        = 'mobile_no';

    const ACCOUNT_DETAILS_VPA   = 'vpa';
    const ACCOUNT_DETAILS_IFSC  = 'ifsc';
    const ACCOUNT_DETAILS_NAME  = 'name';

    const SUCCESS = ['success', 'deemed'];

    //
    // This field is manually added in MIS for creating unexpected payment
    // against a reference number (rrn). Its the RRN of the payment to be
    // created but still taking in input as a confirmation token of unexpected
    // payment creation.
    //
    const UNEXPECTED_PAYMENT_REF_ID = 'unexpected_payment_ref_id';

    //
    // Below two column are also not present in recon file and
    // are added manually. These are required to find the terminal.
    //
    const UPI_MERCHANT_ID           = 'upi_merchant_id';
    const UPI_MERCHANT_CHANNEL_ID   = 'upi_merchant_channel_id';

    const BLACKLISTED_COLUMNS = [
        self::ACCOUNT_CUST_NAME,
        self::VPA,
        self::COLUMN_MOBILE_NO,
    ];

    protected function getPaymentId(array $row)
    {
        //
        // check if the recon status is failed. Return refund
        // Id as null so that such rows don't get processed.
        //

        $paymentIdColumn = array_first(self::COLUMN_PAYMENT_ID, function ($pid) use ($row)
        {
            return (isset($row[$pid]) === true);
        });

        $paymentId = $row[$paymentIdColumn] ?? null;

        if ($this->getReconPaymentStatus($row) === Payment\Status::FAILED)
        {
            $this->setFailUnprocessedRow(false);

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::MIS_FILE_PAYMENT_FAILED ,
                    'payment_id' => $paymentId,
                    'gateway'    => $this->gateway
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::MIS_FILE_PAYMENT_FAILED);

            return null;
        }

        if (UniqueIdEntity::verifyUniqueId($paymentId, false) === false)
        {
            return $this->getPaymentIdForUnexpectedPayment($row);
        }

        return $paymentId;
    }

    /**
     * Sometimes we don't get payment id in the expected column.
     * So, this function utilises the rrn received in the MIS,
     * and fetches the payment id from the upi repo.
     *
     * @param $row
     * @return null|string
     */
    protected function getPaymentIdForUnexpectedPayment($row)
    {
        $referenceNumber = $this->getReferenceNumber($row);

        $this->formatUpiRrn($referenceNumber);

        $upiEntity = $this->repo->upi->fetchByNpciReferenceIdAndGateway($referenceNumber, Gateway::UPI_AXIS);

        if (empty($upiEntity) === false)
        {
            return $upiEntity->getPaymentId();
        }

        // Fetch ups gateway entity if present
        $upsEntity = $this->fetchUpsGatewayEntityByRrn($referenceNumber, Gateway::UPI_AXIS);

        if (empty($upsEntity) === false)
        {
            return $upsEntity['payment_id'];
        }

        if (empty($row[self::UNEXPECTED_PAYMENT_REF_ID]) === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'            => Base\InfoCode::UNEXPECTED_PAYMENT,
                    'payment_reference_id' => $referenceNumber,
                    'gateway'              => $this->gateway,
                    'batch_id'             => $this->batchId,
                ]);

                return null;
        }

        return $this->attemptToCreateUnexpectedPayment($referenceNumber, $row);
    }

    /**
     * Attempts to create unexpected payment
     * Returns payment_id if attempt is successful,
     * null otherwise.
     * @param string $rrn
     * @param array $input
     * @return string|null
     */
    protected function attemptToCreateUnexpectedPayment(string $rrn, array $input)
    {
        $paymentId = null;
        //
        // Prepared callback input required for creating unexpected payment
        //
        $callbackInput = [
            'customerVpa'            => $input[self::VPA],
            'merchantId'             => $input[self::UPI_MERCHANT_ID] ?? null,
            'merchantChannelId'      => $input[self::UPI_MERCHANT_CHANNEL_ID] ?? null,
            'merchantTransactionId'  => $input[self::COLUMN_PAYMENT_ID[0]] ??
                ($input[self::COLUMN_PAYMENT_ID[1]] ?? null),
            'transactionTimestamp'   => $input[self::COLUMN_TRANSACTION_DATE[0]] ??
                ($input[self::COLUMN_TRANSACTION_DATE[1]] ?? null),
            'transactionAmount'      => $input[self::COLUMN_PAYMENT_AMOUNT],
            'gatewayTransactionId'   => $input[self::TXN_ID],
            'gatewayResponseCode'    => $input[self::RESPCODE],
            'gatewayResponseMessage' => $input[self::RESPONSE],
            'rrn'                    => $input[self::RRN],
            'checksum'               => null,
        ];

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'infoCode'                  => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATE_INITIATED,
                'rrn'                       => $input[self::RRN],
                'gateway_payment_id'        => $callbackInput['merchantTransactionId'],
                'unexpected_payment_ref_id' => $input[self::UNEXPECTED_PAYMENT_REF_ID],
                'gateway'                   => $this->gateway,
                'batch_id'                  => $this->batchId,
            ]);
        try
        {
            $response = (new Payment\Service)->unexpectedCallback(
                $callbackInput,
                $callbackInput['merchantTransactionId'],
                Gateway::UPI_AXIS);

            if (empty($response['payment_id']) === false)
            {
                $paymentId = $response['payment_id'];

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'infoCode'              => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATED,
                        'payment_id'            => $paymentId,
                        'rrn'                   => $rrn,
                        'gateway_payment_id'    => $callbackInput['merchantTransactionId'],
                        'gateway'               => $this->gateway,
                        'batch_id'              => $this->batchId,
                    ]);
            }
            else
            {
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'infoCode'              => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                        'rrn'                   => $rrn,
                        'gateway_payment_id'    => $callbackInput['merchantTransactionId'],
                        'gateway'               => $this->gateway,
                        'batch_id'              => $this->batchId,
                    ]);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                [
                    'rrn'                       => $rrn,
                    'gateway_payment_id'        => $callbackInput['merchantTransactionId'],
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]
            );
        }

        return $paymentId;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::RRN] ?? null;
    }

    protected function getReconVpa($row)
    {
        return $row[self::VPA] ?? null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::TXN_ID] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $rowStatus = strtolower($row[self::RESPONSE] ?? null);

        if (in_array($rowStatus, self::SUCCESS) === true)
        {
            return Payment\Status::AUTHORIZED;
        }

        return Payment\Status::FAILED;
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
        if (isset($row[self::COLUMN_PAYMENT_AMOUNT]) === false)
        {
            return 0;
        }

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT]);
    }

    public function getGatewayPayment($paymentId)
    {
        try
        {
            return $this->repo->upi->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
        }

        catch (DbQueryException $ex)
        {
            $this->trace->traceException($ex);

            return null;
        }
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $npciRefId = $gatewayPayment->getNpciReferenceId();

        if ((empty($npciRefId) === false) and
            ($npciRefId !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'message'                   => 'Npci Reference id is not same as in recon',
                    'info_code'                 => $infoCode,
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'payment_status'            => $this->payment->getStatus(),
                    'db_reference_number'       => $npciRefId,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        // We will only update the RRN if it is empty
        $gatewayPayment->setNpciReferenceId($referenceNumber);
    }

    protected function getAccountDetails($row)
    {
        $accountDetails = [];

        if (empty($row[self::VPA]) === false)
        {
            $accountDetails[self::ACCOUNT_DETAILS_VPA] = $row[self::VPA];
        }

        if (empty($row[self::IFSC]) === false)
        {
            $accountDetails[self::ACCOUNT_DETAILS_IFSC] = $row[self::IFSC];
        }

        if (empty($row[self::ACCOUNT_CUST_NAME]) === false)
        {
            $accountDetails[self::ACCOUNT_DETAILS_NAME] = $row[self::ACCOUNT_CUST_NAME];
        }

        return $accountDetails;
    }

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

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getGatewayPaymentId());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayPaymentId($gatewayTransactionId);
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
            Fields::RRN   => $this->getReferenceNumber($row),
            Entity::VPA   => $this->getReconVpa($row),
            'acquirer' => [
                Payment\Entity::VPA         => $this->getReconVpa($row),
                Payment\Entity::REFERENCE16 => $this->payment->getReference16()
            ],
            'upi' => [
                'customer_reference'    => $this->getReferenceNumber($row),
                'customer_vpa'          => $this->getReconVpa($row),
                'npci_reference_id'     => $this->getReferenceNumber($row),
                'vpa'                   => $this->getReconVpa($row),
            ]
        ];
    }
}
