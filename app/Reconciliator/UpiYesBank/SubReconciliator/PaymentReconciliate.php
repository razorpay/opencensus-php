<?php

namespace RZP\Reconciliator\UpiYesBank\SubReconciliator;

use RZP\Exception;
use RZP\Models\QrCode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Upi\Sbi\Action;
use RZP\Models\Payment\Gateway;
use RZP\Models\QrPayment\Entity;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception\BadRequestException;
use RZP\Reconciliator\Base\Reconciliate;
use RZP\Reconciliator\Base\SubReconciliator\Upi;
use RZP\Reconciliator\Base\SubReconciliator\Upi\Constants;

class PaymentReconciliate extends Upi\UpiPaymentServiceReconciliate
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

        if ($this->isQrCodePayment($row) === true)
        {
            $referenceNumber = $this->getReferenceNumber($row);

            $this->trace->info(
                TraceCode::RECON_QR_TRANSACTION_RECEIVED,
                [
                    'payment_reference_id' => $referenceNumber,
                    'payment_id'           => $paymentId,
                    'gateway'              => $this->gateway,
                    'batch_id'             => $this->batchId
                ]);
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

        if (UniqueIdEntity::verifyUniqueId($paymentId, false) === false)
        {
            return $this->getPaymentIdForUnexpectedPayment($row);
        }

        return $this->getUpiAuthorizePaymentId($paymentId, $row);
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
        $paymentId = null;

        $referenceNumber = $this->getReferenceNumber($row);

        $this->formatUpiRrn($referenceNumber);

        $upiEntity = $this->repo->upi->fetchByNpciReferenceIdAndGateway($referenceNumber, $gateway = Gateway::UPI_YESBANK);

        if (empty($upiEntity) === false)
        {
            return $upiEntity->getPaymentId();
        }
        // Fetch ups gateway entity if present
        $upsEntity = $this->fetchUpsGatewayEntityByRrn($referenceNumber, $gateway = Gateway::UPI_YESBANK);

        if (empty($upsEntity) === false)
        {
            return $upsEntity['payment_id'];
        }

        return $this->createUnexpectedPayment($referenceNumber, $row);
    }

    /** We create new payment and return the paymentId for the reconciliation
     * @param string $referenceNumber
     * @param array $row
     * @return mixed|null
     */
    protected function createUnexpectedPayment(string $referenceNumber, array $row)
    {
        $paymentId = null;
        //
        // Prepare callback input required for creating unexpected payment
        //
        $callbackInput = $this->generateCallbackData($row);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'infoCode'                  => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATE_INITIATED,
                'rrn'                       => $row[self::CUSTOMER_REF_NO],
                'unexpected_payment_ref_id' => $row[self::ORDER_NUMBER],
                'gateway'                   => $this->gateway,
                'batch_id'                  => $this->batchId,
            ]);

        try
        {
            $response = (new Payment\Service)->unexpectedCallback($callbackInput, $row[self::ORDER_NUMBER], Gateway::UPI_YESBANK);

            $traceData = [
                            'rrn'                   => $referenceNumber,
                            'gateway_payment_id'    => $row[self::ORDER_NUMBER],
                            'gateway'               => $this->gateway,
                            'batch_id'              => $this->batchId,
                        ];

            $infoCode = Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED;

            if (empty($response['payment_id']) === false)
            {
                $paymentId = $response['payment_id'];

                $traceData['payment_id'] = $paymentId;

                $infoCode = Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATED;
            }

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'infoCode'              => $infoCode,
                    'data'                  => $traceData,
                ]);

            return $paymentId;
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                [
                    'rrn'                       => $referenceNumber,
                    'gateway_payment_id'        => $row[self::ORDER_NUMBER],
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]
            );
        }

        return $paymentId;

    }

    protected function isQrCodePayment(array $row)
    {
        $orderNo = trim($row[self::ORDER_NUMBER]);

        $suffixLength = strlen(QrCode\Constants::QR_CODE_V2_TR_SUFFIX);

        return ((strlen($orderNo) >= ($suffixLength + QrCode\Entity::ID_LENGTH)) and
                (str_ends_with($orderNo, QrCode\Constants::QR_CODE_V2_TR_SUFFIX)));
    }

    protected function getUpiAuthorizePaymentId(string $paymentId, array $row)
    {
        $referenceNumber = $this->getReferenceNumber($row);

        $upiAuthorizeEntity = $this->getUpiExpectedEntity($paymentId, $row);

        if (empty($upiAuthorizeEntity) === false)
        {
            $this->gatewayPayment = $upiAuthorizeEntity;

            return $upiAuthorizeEntity->getPaymentId();
        }

        $upsEntity = $this->fetchUpsGatewayEntityByPaymentId($paymentId, $this->gatewayName);

        // Fetch ups entity and get expected paymentId
        if (empty($upsEntity) === false)
        {
            return $this->getUpsExpectedPayment($paymentId, $upsEntity, $row);
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code'            => TraceCode::RECON_MISMATCH,
                'info_code'             => Base\InfoCode::PAYMENT_ABSENT,
                'payment_reference_id'  => $referenceNumber,
                'payment_id'            => $paymentId,
                'gateway'               => $this->gateway,
                'batch_id'              => $this->batchId
            ]);

        return $paymentId;
    }

    /** Prepare callback input required for creating unexpected payment
     * as per the pre_process response format
     * @param array $row
     * @return array
     */
    protected function generateCallbackData(array $row)
    {
        $callbackData = [];

        $callbackData['success'] = true;

        $callbackData['data']['payment'] = [
            'amount_authorized'    => $this->getReconPaymentAmount($row),
            'currency'             => 'INR',
        ];

        $callbackData['data']['terminal'] = [
            'gateway_merchant_id'    => $row[self::PG_MERCHANT_ID],
            'gateway'                => $this->gatewayName,
        ];

        $callbackData['data']['upi'] = [
            'gateway_merchant_id'   => $row[self::PG_MERCHANT_ID],
            'merchant_reference'    => $row[self::ORDER_NUMBER],
            'npci_reference_id'     => $row[self::CUSTOMER_REF_NO],
            'gateway_payment_id'    => $row[self::TRANS_REF_NUMBER],
            'status_code'           => $row[self::TRANSACTION_STATUS],
            'vpa'                   => $row[self::PAYER_VIRTUAL_ADDRESS],
        ];

        $callbackData['data']['version'] = 'v2';

        return $callbackData;
    }
}
