<?php

namespace RZP\Models\Merchant\FileProcessor\Irctc;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund\Entity;
use RZP\Models\Merchant\FileProcessor\TypeProcessor;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Refund extends TypeProcessor
{
    const MERCHANT_REFERENCE = 'merchant_reference';
    const REFUND_TYPE        = 'refund_type';
    const REFUND_AMOUNT      = 'refund_amount';
    const PAYMENT_ID         = 'payment_id';
    const CANCELLATION_DATE  = 'cancellation_date';
    const PAYMENT_AMOUNT     = 'payment_amount';
    const CANCELLATION_ID    = 'cancellation_id';

    const R_TYPE             = 'R';
    const C_TYPE             = 'C';

    const HEADERS = [
        self::MERCHANT_REFERENCE,
        self::REFUND_TYPE,
        self::REFUND_AMOUNT,
        self::PAYMENT_ID,
        self::CANCELLATION_DATE,
        self::PAYMENT_AMOUNT,
        self::CANCELLATION_ID,
    ];

    protected $type;

    public function __construct($type = self::R_TYPE)
    {
        parent::__construct();

        $this->type = $type;
    }

    protected function processEntry(array $row)
    {
        $paymentId = $row[self::PAYMENT_ID];

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $processor = 'process' . studly_case($this->type) .'TypeRefunds';

        $this->$processor($row, $payment);
    }

    protected function processRTypeRefunds(array $row, Payment\Entity $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $input = [
            Entity::RECEIPT => $row[self::CANCELLATION_ID . '_' . self::MERCHANT_REFERENCE],
            Entity::NOTES   => [
                'reservation_id' => $row[self::MERCHANT_REFERENCE],
                'receipt'        => $row[self::CANCELLATION_ID],
                'refund_type'    => $row[self::REFUND_TYPE],
            ],
        ];

        $paymentProcessor->createRefundFromMerchantFile($payment, $input);
    }

    protected function processCTypeRefunds(array $row, Payment\Entity $payment)
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
            Entity::RECEIPT => $row[self::CANCELLATION_ID . '_' . self::MERCHANT_REFERENCE],
            Entity::NOTES   => [
                'reservation_id' => $row[self::MERCHANT_REFERENCE],
                'receipt'        => $row[self::CANCELLATION_ID],
                'refund_type'    => $row[self::REFUND_TYPE],
            ],
        ];

        $paymentProcessor->createRefundFromMerchantFile($payment, $input);
    }

    public function getHeaders()
    {
        return self::HEADERS;
    }

    public function getDelimiter()
    {
        return '|';
    }

    protected function getId(array $entry)
    {
        return $entry[self::PAYMENT_ID];
    }
}
