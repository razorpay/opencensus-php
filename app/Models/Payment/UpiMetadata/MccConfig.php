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
    const MAX_INTENT_AMOUNT     = 'max_intent_amount';

    protected $map = [
        '6540' => [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => false,
            self::MAX_COLLECT_AMOUNT    => 20000000,
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
        "8011"=> [
		    self::IS_INTENT_ALLOWED     => true,
		    self::IS_COLLECT_ALLOWED    => true,
		    self::MAX_COLLECT_AMOUNT    => 50000000,
		    self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
        "8021"=> [
            self::IS_INTENT_ALLOWED     => true,
		    self::IS_COLLECT_ALLOWED    => true,
		    self::MAX_COLLECT_AMOUNT    => 50000000,
		    self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8031"=> [
            self::IS_INTENT_ALLOWED     => true,
		    self::IS_COLLECT_ALLOWED    => true,
		    self::MAX_COLLECT_AMOUNT    => 50000000,
		    self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8041"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8042"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8049"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8050"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8062"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8071"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8099"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "742"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
        "8211"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8220"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8241"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8244"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8249"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
	    ],
	    "8299"=> [
            self::IS_INTENT_ALLOWED     => true,
            self::IS_COLLECT_ALLOWED    => true,
            self::MAX_COLLECT_AMOUNT    => 50000000,
            self::MAX_INTENT_AMOUNT     => 50000000,
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

        $maxIntentAmount = array_get($this->config, self::MAX_INTENT_AMOUNT, null);

        // Any amount greater than the allowed amount
        if ((is_integer($maxIntentAmount) === true) and
            ($payment->getAmount() > $maxIntentAmount))
        {
            $this->throwException(ErrorCode::BAD_REQUEST_PAYMENT_UPI_AMOUNT_LIMIT_EXCEEDED, Payment\Method::UPI);
        }

    }

    public function validateCollectPayment(Payment\Entity $payment, $tokenStatus)
    {
        $isCollectAllowed = array_get($this->config, self::IS_COLLECT_ALLOWED, null);

        // Hard check on config
        if (($isCollectAllowed === false) and ($tokenStatus !== 'confirmed'))
        {
            $this->throwException(ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_MCC_BLOCKED);
        }

        $maxCollectAmount = array_get($this->config, self::MAX_COLLECT_AMOUNT, null);

        // Any amount greater than the allowed amount
        if ((is_integer($maxCollectAmount) === true) and
            ($payment->getAmount() > $maxCollectAmount))
        {
            $this->throwException(ErrorCode::BAD_REQUEST_PAYMENT_UPI_AMOUNT_LIMIT_EXCEEDED, Payment\Method::UPI);
        }
    }

    private function throwException(string $code, string $method = '')
    {
        $exception = new BadRequestException($code, null, [
            'mcc' => $this->mcc
        ]);

        if (empty($method) === false)
        {
            $exception->getError()->setPaymentMethod(Payment\Method::UPI);
        }

        throw $exception;
    }
}
