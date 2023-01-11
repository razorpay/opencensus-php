<?php

namespace RZP\Reconciliator\UpiHdfc\SubReconciliator;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Mindgate;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Upi\Mindgate\ResponseFields;
use RZP\Reconciliator\Base\SubReconciliator\Upi\UpiPaymentServiceReconciliate;

class PaymentReconciliate extends UpiPaymentServiceReconciliate
{
    const ORDER_ID                  = 'order_id';

    const TXN_REFERENCE_NUMBER      = 'txn_ref_no_rrn';

    const SETTLEMENT_DATE           = 'settlement_date';

    const CURRENCY                  = 'currency';

    const COLUMN_PAYMENT_AMOUNT     = 'transaction_amount';

    // This field is manually added in MIS file by FinOps, for creating unexpected payment
    // callback key is MeRes data received in callback
    const CALLBACK_KEY              = 'callback_key';

    // This field is manually added in MIS for creating unexpected payment against a reference number (rrn)
    // use this only if we don't have callback key. Its the RRN of the payment to be created but still taking in input
    // as a confirmation token of unexpected payment creation.
    const UNEXPECTED_PAYMENT_REF_ID = 'unexpected_payment_ref_id';

    const UPI_MERCHANT_ID           = 'upi_merchant_id';

    const TRANSACTION_REQ_DATE      = 'transaction_req_date';

    const PAYER_VPA                 = 'payer_vpa';

    const MERCHANT_VPA              = 'merchant_vpa';

    const BLACKLISTED_COLUMNS = [
        self::PAYER_VPA,
        self::MERCHANT_VPA,
    ];

    /**
     * If we are not able to find payment id to reconcile,
     * this ratio defines the minimum proportion of columns to be filled in a valid row.
     * In UPI HDFC MPR, last 10  rows has 1 column as  summary data and rest is empty.
     * Therefore, if less than .05 of data is present, we don't mark row as failure
     */
    const MIN_ROW_FILLED_DATA_RATIO = .05;

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::ORDER_ID];

        if (empty($paymentId) === true)
        {
            $this->evaluateRowProcessedStatus($row);
        }

        //
        // MIS file contains many direct settled payments, for which order_id doesn't contain
        // our payment_id, skipping such rows but marking it as failed to notify the number
        // of such transactions after recon.
        //
        else if (UniqueIdEntity::verifyUniqueId($paymentId, false) === false)
        {
            $paymentId = $this->fetchOrCreatePaymentId($paymentId, $row);
        }

        return $paymentId;
    }

    protected function fetchOrCreatePaymentId($gatewayPaymentId, $row)
    {
        $rrn = $this->getReferenceNumber($row);

        //
        // if rrn or upi_merchant_id is empty, return null
        // as we can't create the payment even if needed.
        //
        if ((empty($rrn) === true) or
            (empty($row[self::UPI_MERCHANT_ID]) === true))
        {
            return null;
        }

        $paymentId = null;

        //
        // Check if payment already created in our system.
        //
        // Note : Here we have the valid RRN but we do not have
        // razorpay's payment_id in the ORDER_ID column of mis,
        // So we are fetching our payment_id using RRN.
        // Such cases happen when we are not able to create payment
        // during callback for some payments and thus we don't get
        // our payment id for these rows in mis.
        //

        $this->formatUpiRrn($rrn);

        $upiEntity = $this->repo->upi->fetchByNpciReferenceIdAndGateway($rrn , $gateway = Gateway::UPI_MINDGATE);

        if (empty($upiEntity) === false)
        {
            $paymentId = $upiEntity->getPaymentId();

            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'infoCode'              => Base\InfoCode::UNEXPECTED_PAYMENT_ALREADY_EXISTS,
                    'payment_id'            => $paymentId,
                    'rrn'                   => $rrn,
                    'gateway_payment_id'    => $gatewayPaymentId,
                    'gateway'               => $this->gateway,
                ]);

            return $paymentId;
        }

        $upiTransfer = $this->repo->upi_transfer->findByNpciReferenceIdAndGateway($rrn,  $gateway = Gateway::UPI_MINDGATE);

        if ($upiTransfer !== null)
        {
            return $upiTransfer->payment->getId();
        }

        // Fetch ups gateway entity if present
        $upsEntity = $this->fetchUpsGatewayEntityByRrn($rrn, $gateway = Gateway::UPI_MINDGATE);

        if (empty($upsEntity) === false)
        {
            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'infoCode'              => Base\InfoCode::UNEXPECTED_PAYMENT_ALREADY_EXISTS,
                    'payment_id'            => $upsEntity['payment_id'],
                    'rrn'                   => $rrn,
                    'gateway_payment_id'    => $gatewayPaymentId,
                    'gateway'               => $this->gateway,
                ]);

            return $upsEntity['payment_id'];
        }

        if ($paymentId === null)
        {
            $paymentId = $this->createPayment($rrn, $gatewayPaymentId, $row);
        }

        return $paymentId;
    }

    protected function createPayment($rrn, $gatewayPaymentId, $row)
    {
        $paymentId = null;

        //
        // Check if we have CALLBACK_KEY field added in the row.
        // If it is there, we will use it to create the payment
        //
        // Note: This field is manually added in MIS file by FinOps
        // team in rare cases when some payments could not be created
        // due to issues with callback, though these payments were
        // successfully created at upi_hdfc side. During MIS processing
        // these payments were marked as 'Unexpected_payment'
        //
        // FinOps manually adds this column for these unexpected payments
        // and uploads the file for reconciliation and we create the payment with the
        // help of this CALLBACK_KEY.
        //

        try
        {
            if (empty($row[self::CALLBACK_KEY]) === false)
            {
                $input = [
                    ResponseFields::CALLBACK_RESPONSE_KEY   => $row[self::CALLBACK_KEY],
                    ResponseFields::CALLBACK_RESPONSE_PGMID => $row[self::UPI_MERCHANT_ID],
                ];

                $response = $this->processUnexpectedPayment($input);

            }
            else
            {
                $response = $this->processUnexpectedPaymentWithoutCallbackKey($row);
            }

            if (empty($response['payment_id']) === false)
            {
                $paymentId = $response['payment_id'];

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'infoCode'              => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATED,
                        'payment_id'            => $paymentId,
                        'rrn'                   => $rrn,
                        'gateway_payment_id'    => $gatewayPaymentId,
                        'gateway'               => $this->gateway,
                    ]);
            }
            else
            {
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'infoCode'      => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATION_FAILED,
                        'rrn'           => $rrn,
                        'payment_id'    => $gatewayPaymentId,
                        'gateway'       => $this->gateway,
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
                    'rrn'           => $rrn,
                    'payment_id'    => $gatewayPaymentId,
                    'gateway'       => $this->gateway,
                ]
            );
        }

        return $paymentId;
    }

    public function processUnexpectedPayment($input, $gatewayDriver = Gateway::UPI_MINDGATE)
    {
        $gatewayObject = new Mindgate\Gateway;

        $input = $gatewayObject->preProcessServerCallback($input);

        $paymentId = $gatewayObject->getPaymentIdFromServerCallback($input);

        $response = (new Payment\Service)->unexpectedCallback($input, $paymentId, $gatewayDriver);

        return $response;
    }

    public function processUnexpectedPaymentWithoutCallbackKey($input, $gatewayDriver = Gateway::UPI_MINDGATE)
    {
        //
        // Prepared callback input required for creating unexpected payment
        //
        $callbackInput = [
            'payment_id'            => $input[self::ORDER_ID],
            'amount'                => $input[self::COLUMN_PAYMENT_AMOUNT],
            'txn_auth_date'         => $input[self::TRANSACTION_REQ_DATE],
            'status'                => 'SUCCESS',
            'status_description'    => 'Transaction success',
            'respcode'              => '00',
            'approval_no'           => 'NA',
            'payer_va'              => $input[self::PAYER_VPA],
            'npci_upi_txn_id'       => $input[self::TXN_REFERENCE_NUMBER],
            'pgMerchantId'          => $input[self::UPI_MERCHANT_ID],
        ];

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'infoCode'                  => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATE_INITIATED,
                'rrn'                       => $input[self::TXN_REFERENCE_NUMBER],
                'gateway_payment_id'        => $input[self::ORDER_ID],
                'gateway'                   => $this->gateway,
            ]);

        $response = (new Payment\Service)->unexpectedCallback($callbackInput, $input[self::ORDER_ID], $gatewayDriver);

        return $response;
    }

    protected function generateCallbackData(array $row)
    {
        $callbackData = [
            'payment_id'            => $row[self::ORDER_ID],
            'amount'                => $row[self::COLUMN_PAYMENT_AMOUNT],
            'txn_auth_date'         => $row[self::TRANSACTION_REQ_DATE],
            'status'                => 'SUCCESS',
            'status_description'    => 'Transaction success',
            'respcode'              => '00',
            'approval_no'           => 'NA',
            'payer_va'              => $row[self::PAYER_VPA],
            'npci_upi_txn_id'       => $row[self::TXN_REFERENCE_NUMBER],
            'pgMerchantId'          => $row[self::UPI_MERCHANT_ID],
        ];

        return $callbackData;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            //
            // As we are getting more than 100s of amount_mismatch alerts (of just 1 paisa),
            // so temporarily removing slack alert for UpiHdfc. FinOps will continue to get
            // daily report for amount mismatch from Sumologic and take action as usual.
            // This is just to reduce noise on recon slack channel
            //
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
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

    protected function getReferenceNumber($row)
    {
        return $row[self::TXN_REFERENCE_NUMBER] ?? null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        $settledAt = $row[self::SETTLEMENT_DATE] ?? null;

        if (empty($settledAt) === true)
        {
            return null;
        }

        $gatewaySettledAtTimestamp = null;

        try
        {
            $gatewaySettledAtTimestamp = Carbon::parse($settledAt)->setTimezone(Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::INFO,
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code' => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'message'   => 'Unable to parse settlement date -> ' . $ex->getMessage(),
                    'date'      => $settledAt,
                    'gateway'   => $this->gateway,
                ]);
        }

        return $gatewaySettledAtTimestamp;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->upi->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $dbReferenceNumber = trim($gatewayPayment->getNpciReferenceId());

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $referenceNumber) and
            ($dbReferenceNumber !== 'NA'))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setNpciReferenceId($referenceNumber);
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT] ?? null);
    }

    /**
     * This function evaluate and marks the row processing as success or failure based on
     * percentage of data available in a row.
     *
     * @param $row
     */
    private function evaluateRowProcessedStatus(array $row)
    {
        $nonEmptyData = array_filter($row, function($value) {
            return filled($value);
        });

        $rowFilledRatio = count($nonEmptyData) / count($row);

        if ($rowFilledRatio < self::MIN_ROW_FILLED_DATA_RATIO)
        {
            $this->setFailUnprocessedRow(false);
        }
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
                Payment\Entity::VPA         => $row[self::PAYER_VPA],
            ]
        ];
    }
}
