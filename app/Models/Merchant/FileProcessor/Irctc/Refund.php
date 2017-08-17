<?php

namespace RZP\Models\Merchant\FileProcessor\Irctc;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund\Entity;
use RZP\Models\Merchant\FileProcessor\Base;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Refund extends Base
{
    const MERCHANT_REFERENCE = 'merchant_reference';
    const REFUND_TYPE        = 'refund_type';
    const REFUND_AMOUNT      = 'refund_amount';
    const PAYMENT_ID         = 'payment_id';
    const CANCELLATION_DATE  = 'cancellation_date';
    const PAYMENT_AMOUNT     = 'payment_amount';
    const CANCELLATION_ID    = 'cancellation_id';

    const HEADERS = [
        self::MERCHANT_REFERENCE,
        self::REFUND_TYPE,
        self::REFUND_AMOUNT,
        self::PAYMENT_ID,
        self::CANCELLATION_DATE,
        self::PAYMENT_AMOUNT,
        self::CANCELLATION_ID,
    ];

    protected $refundType;

    public function __construct(array $inputDetails)
    {
        parent::__construct();

        $this->refundType = $inputDetails['extra_input_type'];
    }

    protected function processEntry(array $row)
    {
        $paymentId = $row[self::PAYMENT_ID];

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $type = $row[self::REFUND_TYPE];

        $processed = false;

        if (($this->refundType === 'R') and
            ($type === $this->refundType))
        {
            $this->processRTypeRefunds($row, $payment);

            $processed = true;
        }
        else if (($this->refundType === 'C') and
                ($type === $this->refundType))
        {
            $this->processCTypeRefunds($row, $payment);

            $processed = true;
        }
        else
        {
            throw new Exception\BadRequestException(
                'Refund Type is not valid',
                [
                    'input_refund_type' => $this->refundType,
                    'row_data'          => $row,
                ]
            );
        }

        return $processed;
    }

    protected function processRTypeRefunds($row, $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $input = [
            Entity::RECEIPT => $row[self::CANCELLATION_ID],
            Entity::NOTES   => [
                'receipt' => $row[self::CANCELLATION_ID],
                'refund_type' => $row[self::REFUND_TYPE],
            ]
        ];

        $refund = $paymentProcessor->createRefundFromMerchantFile($payment, $input);
    }

    protected function processCTypeRefunds($row, $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        if ($payment->getStatus() !== Payment\Status::CAPTURED) {
            throw new Exception\BadRequestException(
                'Payment is not Captured for C type refund',
                $row
            );
        }

        $input = [
            Entity::AMOUNT  => intval($row[self::REFUND_AMOUNT] * 100),
            Entity::RECEIPT => $row[self::CANCELLATION_ID],
            Entity::NOTES   => [
                'receipt' => $row[self::CANCELLATION_ID],
                'refund_type' => $row[self::REFUND_TYPE],
            ]
        ];

        $refund = $paymentProcessor->createRefundFromMerchantFile($payment, $input);
    }

    public function getHeaders()
    {
        return self::HEADERS;
    }

    public function getDelimiter()
    {
        return '|';
    }
}
