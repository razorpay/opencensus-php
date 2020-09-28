<?php

namespace RZP\Models\Payment\UpiMetadata;

use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Exception\BadRequestException;

class MccConfig
{
    const IS_INTENT_ALLOWED     = 'is_intent_allowed';
    const IS_COLLECT_ALLOWED    = 'is_collect_allowed';
    const MAX_COLLECT_AMOUNT    = 'max_collect_amount';

    protected $map = [
        '6540' => [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => false,
            self::MAX_COLLECT_AMOUNT    => null,
        ],

        '4812'  => [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 500000,
        ],

        '4814'  => [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 500000,
        ],
    ];

    /**
     * @var string
     */
    protected $mcc;

    /**
     * @var Array
     */
    protected $config = [];

    public function __construct(string $mcc)
    {
        $this->mcc = $mcc;

        if (isset($this->map[$mcc]) === true)
        {
            $this->config = $this->map[$mcc];
        }
    }

    public function validateIntentPayment(Payment\Entity $payment)
    {
        // No validation for intent as of now
        return;
    }

    public function validateCollectPayment(Payment\Entity $payment)
    {
        $isCollectAllowed = array_get($this->config, self::IS_COLLECT_ALLOWED, null);

        // Hard check on config
        if ($isCollectAllowed === false)
        {
            $this->throwException(ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_MCC_BLOCKED);
        }

        $maxCollectAmount = array_get($this->config, self::MAX_COLLECT_AMOUNT, null);

        // Any amount greater than the allowed amount
        if ((is_integer($maxCollectAmount) === true) and
            ($payment->getAmount() > $maxCollectAmount))
        {
            $this->throwException(ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_MCC_AMOUNT_LIMIT_REACHED);
        }
    }

    private function throwException(string $code)
    {
        throw new BadRequestException($code, null, [
            'mcc' => $this->mcc
        ]);
    }

}
