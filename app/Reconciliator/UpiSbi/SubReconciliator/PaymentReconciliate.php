<?php

namespace RZP\Reconciliator\UpiSbi\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Upi\Sbi\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\SubReconciliator\Upi;

class PaymentReconciliate extends Upi\UpiPaymentServiceReconciliate
{
    use Base\UpiReconTrait;
    /**
     * @see https://drive.google.com/drive/u/0/folders/0B1kf6HOmx7JBTmMzTXgwQVRrNm8
     */

    const ORDER_NUMBER          = 'order_no';
    const TRANS_REF_NUMBER      = 'trans_ref_no';
    const TRANSACTION_STATUS    = 'transaction_status';
    const TRANSACTION_AMOUNT    = 'transaction_amount';
    const PAYER_VIRTUAL_ACCOUNT = 'payer_virtual_account';
    const PAYER_VIRTUAL_ADDRESS = 'payer_virtual_address';
    const PAYEE_VIRTUAL_ADDRESS = 'payee_virtual_address';
    const PAYEE_VIRTUAL_ACCOUNT = 'payee_virtual_account';
    const PAYER_ACCOUNT_NAME    = 'payer_ac_name';
    const PAYER_ACCOUNT_NO      = 'payer_ac_no';
    const CUSTOMER_REF_NO       = 'customer_ref_no';
    const PG_MERCHANT_ID        = 'pg_merchant_id';
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

        if (UniqueIdEntity::verifyUniqueId($paymentId, false) === false)
        {
            return $this->getPaymentIdForUnexpectedPayment($row);
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

        $upiEntity = $this->repo->upi->fetchByNpciReferenceIdAndGateway($referenceNumber, $gateway = Gateway::UPI_SBI);

        if (empty($upiEntity) === true)
        {
            $paymentId = $this->attemptToCreateUnexpectedPayment($referenceNumber, $row);
        }
        else
        {
            $paymentId = $upiEntity->getPaymentId();
        }

        return $paymentId;
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
        $callbackInput['payment'] = [
                        'method'    =>  'upi',
                        'amount'    => $this->getReconPaymentAmount($input),
                        'currency'  => 'INR',
                        'vpa'       => $input[self::PAYER_VIRTUAL_ADDRESS],
                        'contact'   => '+919999999999',
                        'email'     => 'void@razorpay.com'
        ];

        $callbackInput['terminal'] = [
                        'gateway_merchant_id'    =>  $input[self::PG_MERCHANT_ID]
        ];

        $callbackInput['upi'] = [
                        'gateway_merchant_id'   => $input[self::PG_MERCHANT_ID],
                        'merchant_reference'    => $input[self::ORDER_NUMBER],
                        'npci_reference_id'     => $input[self::CUSTOMER_REF_NO],
                        'gateway_payment_id'    => $input[self::TRANS_REF_NUMBER],
                        'status_code'           => $input[self::TRANSACTION_STATUS],
                        'account_number'        => $input[self::PAYER_ACCOUNT_NO],
                        'ifsc'                  => $input[self::PAYER_IFSC_CODE],
                        'vpa'                   => $input[self::PAYER_VIRTUAL_ADDRESS],
        ];

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'infoCode'                  => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATE_INITIATED,
                'rrn'                       => $input[self::CUSTOMER_REF_NO],
                'unexpected_payment_ref_id' => $input[self::ORDER_NUMBER],
                'gateway'                   => $this->gateway,
                'batch_id'                  => $this->batchId,
            ]);

        try
        {
            $response = (new Payment\Service)->unexpectedCallback($callbackInput, $input[self::ORDER_NUMBER], Gateway::UPI_SBI);

            if (empty($response['payment_id']) === false)
            {
                $paymentId = $response['payment_id'];

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'infoCode'              => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_CREATED,
                        'payment_id'            => $paymentId,
                        'rrn'                   => $rrn,
                        'gateway_payment_id'    => $input[self::ORDER_NUMBER],
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
                        'gateway_payment_id'    => $input[self::ORDER_NUMBER],
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
                    'gateway_payment_id'        => $input[self::ORDER_NUMBER],
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]
            );
        }

        return $paymentId;
    }

    protected function getAmountMarginAllowed()
    {
        //This amount allowed margin should be implemented at each gateway
        return 0;
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
