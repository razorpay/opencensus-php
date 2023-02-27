<?php

namespace RZP\Reconciliator\UpiIcici\SubReconciliator;

use Carbon\Carbon;
use RZP\Gateway\Upi;
use RZP\Models\QrCode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\BharatQr;
use RZP\Trace\TraceCode;
use RZP\Models\UpiTransfer;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Gateway;
use RZP\Models\QrPayment\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Upi\Icici\Action;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Gateway\Upi\Icici\Status as UpiStatus;
use RZP\Gateway\Upi\Icici\Fields as UpiIciciFields;
use RZP\Reconciliator\Base\SubReconciliator\Upi\UpiPaymentServiceReconciliate;

class PaymentReconciliate extends UpiPaymentServiceReconciliate
{
    use Base\BharatQrTrait;
    use Base\UpiReconTrait;

    const SUB_MERCHANT_NAME = 'submerchantname';
    const MERCHANT_TRAN_ID  = 'merchanttranid';
    const SERVICE_TAX       = 'service_tax';
    const BANK_TRANS_ID     = 'banktranid';
    const COMMISSION        = 'commission';
    const STATUS            = 'status';
    const AMOUNT            = 'amount';
    const PAYER_VPA         = 'payerva';

    const MERCHANT_ID        = 'merchantid';
    const DATE               = 'date';
    const TIME               = 'time';
    const REMARK             = 'remark';

    const BLACKLISTED_COLUMNS = [
        self::PAYER_VPA,
    ];

    // This column indicates if we should create unexpected payment
    const UNEXPECTED_PAYMENT_RRN    = 'unexpected_payment_rrn';

    const CALL_BACK_FIELD_MAPPING = [
        UpiIciciFields::MERCHANT_ID         => self::MERCHANT_ID,
        UpiIciciFields::SUBMERCHANT_ID      => self::SUB_MERCHANT_NAME,
        UpiIciciFields::BANK_RRN            => self::BANK_TRANS_ID,
        UpiIciciFields::MERCHANT_TRAN_ID    => self::MERCHANT_TRAN_ID,
        UpiIciciFields::PAYER_VA            => self::PAYER_VPA,
        UpiIciciFields::PAYER_AMOUNT        => self::AMOUNT,
        UpiIciciFields::TXN_STATUS          => self::STATUS,
        UpiIciciFields::TXN_INIT_DATE       => self::DATE,
        UpiIciciFields::TXN_COMPLETION_DATE => self::TIME,
    ];

    const UPI_TRANSFER_MERCHANT_ID = '403343';

    /**
     * Actual name for the gateway
     */
    protected $gatewayName  = Gateway::UPI_ICICI;

    protected function getPaymentId(array $row)
    {
        if ($this->getReconPaymentStatus($row) === Payment\Status::FAILED)
        {
            $this->setFailUnprocessedRow(false);

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::MIS_FILE_PAYMENT_FAILED ,
                    'rrn'        => $this->getReferenceNumber($row),
                    'gateway'    => $this->gateway,
                    'batch_id'   => $this->batchId
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::MIS_FILE_PAYMENT_FAILED);

            return null;
        }

        if ($this->isQrCodePayment($row) === true)
        {
            return $this->getPaymentIdFromBharatQrEntity($row);
        }
        else if ($this->isUpiTransferPaymentTxn($row) === true)
        {
            return $this->fetchOrCreatePaymentIdForUpiTransfer($row);
        }
        else
        {
            return $this->getPaymentIdFromUpi($row);
        }
    }

    protected function isQrCodePayment(array $row)
    {
        if ((isset($row[self::SUB_MERCHANT_NAME]) === true) and
            (strpos($row[self::SUB_MERCHANT_NAME], 'BHARAT QR') !== false))
        {
            return true;
        }

        if (isset($row[self::MERCHANT_TRAN_ID]) === false)
        {
            return false;
        }

        $merchantTranId = trim($row[self::MERCHANT_TRAN_ID]);

        $suffixLength = strlen(QrCode\Constants::QR_CODE_V2_TR_SUFFIX);

        if ((strlen($merchantTranId) >= ($suffixLength + QrCode\Entity::ID_LENGTH)) and
            (str_ends_with($merchantTranId, QrCode\Constants::QR_CODE_V2_TR_SUFFIX)))
        {
            return true;
        }

       return false;
    }

    protected function getPaymentIdFromBharatQrEntity(array $row)
    {
        $referenceNumber = $this->getReferenceNumber($row);

        if (empty($referenceNumber) === true)
        {
            return null;
        }

        $merchantTranId = "";

        if (isset($row[self::MERCHANT_TRAN_ID]) === true)
        {
            $merchantTranId = trim($row[self::MERCHANT_TRAN_ID]);
        }

        // Mutex key is combination of merchantTranId and RRN, since RRN is alone not unique
        $mutexResource = 'bharatQr_' . $merchantTranId . '_' . $referenceNumber;

        $bqrPaymentId = $this->app['api.mutex']->acquireAndRelease(
            $mutexResource,
            function() use ($row, $referenceNumber)
            {
                $amount = (int)round(($row[self::AMOUNT] * 100));

                $qrCodePayment = $this->repo->bharat_qr->findByProviderReferenceIdAndAmount($referenceNumber, $amount);

                // Fetch payment ID from qr_payment
                if ($qrCodePayment === null)
                {
                    $qrCodePayment = $this->repo
                                          ->qr_payment
                                          ->findByProviderReferenceIdAndGatewayAndAmount($referenceNumber,
                                    Gateway::UPI_ICICI,
                                            $amount);

                    if ((array_key_exists(self::REMARK, $row) === true) and
                        ($qrCodePayment !== null) and
                        ($qrCodePayment->getNotes() === null))
                    {
                        $qrPayment = $this->repo->qr_payment->findOrFail($qrCodePayment->getId());

                        $qrPayment->setNotes(substr($row[self::REMARK], 0, Entity::MAX_NOTES_LENGTH));

                        $this->repo->qr_payment->saveOrFail($qrPayment);
                    }
                }

                if ($qrCodePayment != null)
                {
                    return $qrCodePayment->payment->getId();
                }
                else
                {
                    // Generate callback data from recon row if possible
                    $callbackData = $this->generateCallbackData($row);

                    if ($callbackData === null)
                    {
                        return null;
                    }

                    $paymentId = null;

                    $this->trace->info(
                        TraceCode::RECON_INFO,
                        [
                            'info_code' => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATE_INITIATED,
                            'rrn' => $referenceNumber,
                            'amount' => $amount,
                            'gateway' => $this->gateway,
                            'batch_id' => $this->batchId,
                        ]
                    );

                    // For ICICI the input is a string, usually it's encrypted string but the gateway can handle plaintext
                    $response = (new BharatQr\Service)->processPayment(json_encode($callbackData), 'upi_icici');

                    // Fetch and raise alert if payment still not created
                    $qrCodePayment = $this->repo->bharat_qr->findByProviderReferenceIdAndAmount($referenceNumber, $amount);

                    if ($qrCodePayment === null)
                    {
                        $qrCodePayment = $this->repo->qr_payment->findByProviderReferenceIdAndGatewayAndAmount($referenceNumber, Gateway::UPI_ICICI, $amount);
                    }

                    if ($qrCodePayment === null)
                    {
                        $this->trace->info(
                            TraceCode::RECON_INFO_ALERT,
                            [
                                'infoCode' => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                                'rrn' => $referenceNumber,
                                'amount' => $amount,
                                'response' => $response,
                                'gateway' => $this->gateway,
                                'batch_id' => $this->batchId,
                            ]);

                        return null;
                    }
                    else
                    {
                        $paymentId = $qrCodePayment->payment->getId();

                        $this->trace->info(
                            TraceCode::RECON_INFO,
                            [
                                'infoCode' => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATED,
                                'payment_id' => $paymentId,
                                'rrn' => $referenceNumber,
                                'gateway' => $this->gateway,
                                'batch_id' => $this->batchId,
                            ]);
                    }

                    return $paymentId;
                }
            });

        return $bqrPaymentId;
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

        //
        // For the below fields, we are not getting the data in MIS directly, so
        // add the data accordingly
        //
        $callbackData[UpiIciciFields::TERMINAL_ID]  = null;
        $callbackData[UpiIciciFields::PAYER_NAME]   = null;
        $callbackData[UpiIciciFields::PAYER_MOBILE] = '0000000000';

        // txn init time and completion time can be formulated using Date and time column
        $dateTime = $row[self::DATE] . ' ' . $row[self::TIME];

        // Format date to required format .i.e 20200703202700
        $formattedDate = Carbon::createFromFormat('d/m/Y H:i A', $dateTime)->format('YmdHis');

        $callbackData[UpiIciciFields::TXN_INIT_DATE] = $formattedDate;
        $callbackData[UpiIciciFields::TXN_COMPLETION_DATE] = $formattedDate;

        if(array_key_exists(self::REMARK, $row) === true)
        {
            $callbackData[UpiIciciFields::REMARK] = $row[self::REMARK];
        }

        return $callbackData;
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

        $paymentId = $row[self::MERCHANT_TRAN_ID] ?? null;

        if ((empty($referenceNumber) === true) or (empty($paymentId) === true))
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

        if (empty($upiEntity) === false)
        {
            // Also now since we have found/created a new UPI Entity we will consider
            // this to be the gateway payment id
            $this->gatewayPayment = $upiEntity;

            return $upiEntity->getPaymentId();
        }

        $upsEntity = $this->fetchUpsGatewayEntityByPaymentId($paymentId, $this->gatewayName);

        // Fetch ups entity and get expected paymentId
        if (empty($upsEntity) === false)
        {
            return $this->getUpsExpectedPayment($paymentId, $upsEntity, $row);
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code'           => TraceCode::RECON_MISMATCH,
                'info_code'            => Base\InfoCode::PAYMENT_ABSENT,
                'payment_reference_id' => $row[self::BANK_TRANS_ID],
                'payment_id'           => $paymentId,
                'gateway'              => $this->gateway,
                'batch_id'             => $this->batchId
            ]);

        return $paymentId;

        // Also now since we have found/created a new UPI Entity we will consider
        // this to be the gateway payment id
        $this->gatewayPayment = $upiEntity;

        return $upiEntity->getPaymentId();
    }

    protected function getReconPaymentStatus(array $row)
    {
        // If status is not set, assuming status to be failed
        $status = $row[self::STATUS];

        if ($status === UpiStatus::SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else if ($this->isPaymentStatusFailed($status) === true)
        {
            return Status::FAILED;
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code' => TraceCode::RECON_CRITICAL_ALERT,
                'message'    => 'Recon status is neither success, rejected or failed',
                'payment_id' => $this->payment->getId(),
                'gateway'    => $this->gateway
            ]);

        return Status::FAILED;
    }

    protected function getAmountMarginAllowed($row)
    {
        $terminal = $this->payment->terminal;

        // We will not allow any margin for direct settlement terminals.
        if ($terminal->isDirectSettlement() === true)
        {
            return 0;
        }

        /*
           removing this marginal amount for icici due to security concerns
            (https://razorpay.slack.com/archives/CNP473LRF/p1650375675582489)
         */
        //return 100;
        return 0;
    }

    protected function getReferenceNumber($row)
    {
        return $this->toUpiRrn($row[self::BANK_TRANS_ID] ?? null);
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

    /**
     * Modifying this function to update the rrn from mis,
     * despite mismatch. Changed coz of cases where db had
     * different rrn than the one in mis, and such cases kept increasing.
     *
     * @param string $referenceNumber
     * @param PublicEntity $gatewayPayment
     */
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
        }

        $upiReconciledAt = $gatewayPayment->getReconciledAt();

        // We will update the RRN if the UPI Entity is never marked reconciled
        if (empty($upiReconciledAt) === true)
        {
            $gatewayPayment->setNpciReferenceId($referenceNumber);
        }
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'vpa'                   => $row[self::PAYER_VPA],
            'gateway_payment_id'    => $row[self::BANK_TRANS_ID],
            'acquirer' => [
                Payment\Entity::VPA         => $row[self::PAYER_VPA],
                Payment\Entity::REFERENCE16 => $this->payment->getReference16(),
            ]
        ];
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::AMOUNT]);
    }

    private function isPaymentStatusFailed(string $status)
    {
        return (($status === UpiStatus::REJECT) or ($status === UpiStatus::FAILURE));
    }


    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        //
        // Enabling force Auth for all payments because verify API of upi-icici
        // gives wrong status in case of payment retries (i.e. multiple payments are created at
        // gateway/bank side and verify api return the status of failed payment, even though we
        // have received the payment in MIS file, which means payment got success at bank's side)
        //
        $this->allowForceAuthorization = true;
    }

    private function isUpiTransferPaymentTxn(array $row)
    {
        return ($row[self::MERCHANT_ID] === self::UPI_TRANSFER_MERCHANT_ID);
    }

    private function fetchOrCreatePaymentIdForUpiTransfer(array $row)
    {
        $referenceNumber = $this->getReferenceNumber($row);

        if (empty($referenceNumber) === true)
        {
            return null;
        }

        $upiTransfer = $this->repo->upi_transfer->findByNpciReferenceIdAndGateway($referenceNumber, Gateway::UPI_ICICI);

        if ($upiTransfer !== null)
        {
            return $upiTransfer->payment->getId();
        }

        $callbackData = $this->generateCallbackData($row);

        if ($callbackData === null)
        {
            return null;
        }

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code' => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATE_INITIATED,
                'rrn'       => $referenceNumber,
                'gateway'   => $this->gateway,
                'batch_id'  => $this->batchId
            ]
        );

        $response = (new UpiTransfer\Service())->processUpiTransferPayment(json_encode($callbackData), Gateway::UPI_ICICI);

        $upiTransfer = $this->repo->upi_transfer->findByNpciReferenceIdAndGateway($referenceNumber, Gateway::UPI_ICICI);

        if ($upiTransfer === null)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'infoCode' => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                    'rrn'      => $referenceNumber,
                    'response' => $response,
                    'gateway'  => $this->gateway,
                    'batch_id' => $this->batchId,
                ]);

            return null;
        }

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'infoCode'   => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATED,
                'payment_id' => $upiTransfer->payment->getId(),
                'rrn'        => $referenceNumber,
                'gateway'    => $this->gateway,
                'batch_id'   => $this->batchId,
            ]);

        return $upiTransfer->payment->getId();
    }
}
