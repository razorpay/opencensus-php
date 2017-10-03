<?php

namespace RZP\Models\Qr;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $mutex;

    protected $map = [
        NotificationParams::F002        => Entity::CARD_NUMBER,
        NotificationParams::F003        => Entity::CARD_NETWORK,
        NotificationParams::F004        => Entity::AMOUNT,
        NotificationParams::F011        => Entity::TRACE_NUMBER,
        NotificationParams::F012        => Entity::TRANSACTION_TIME,
        NotificationParams::F013        => Entity::TRANSACTION_DATE,
        NotificationParams::F037        => Entity::RRN,
        NotificationParams::F038        => Entity::PROVIDER_REFERENCE_ID,
        NotificationParams::F039        => Entity::STATUS_CODE,
        NotificationParams::F041        => Entity::GATEWAY_TERMINAL_ID,
        NotificationParams::F042        => Entity::GATEWAY_MERCHANT_ID,
        NotificationParams::F102        => Entity::GATEWAY_TERMINAL_DESC,
        NotificationParams::PURCHASE_ID => Entity::MERCHANT_REFERENCE,
        NotificationParams::SENDER_NAME => Entity::CUSTOMER_NAME,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * @param array $input
     *
     * @return Entity
     */
    public function create(array $input)
    {
        $qr = (new Entity)->build($input);

        return $bankTransfer;
    }

    public function processPayment(array $input)
    {
        $input = $this->getMappedAttributes($input);

        try
        {
            $qr = $this->create($input);

            $this->mutex->acquireAndRelease(
                $input[Entity::PAYEE_ACCOUNT],
                function() use ($qr)
                {
                    (new Processor)->process($qr);
                },
                60,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

            $valid = true;
        }
        catch (Exception\BadRequestValidationFailureException $ex)
        {
            // Returning anything other than a 200 causes Kotak to retry here.
            //
            // However, validation failures are due to Kotak sending the request
            // in wrong format, or (more frequently) the wrong request altogether.
            // So retrying doesn't help us, and will cause unnecessary errors.
            // Best to trace, and return false, to stop the request.
            $this->trace->traceException(
                $ex, Trace::ERROR, TraceCode::BANK_TRANSFER_PROCESSING_FAILED, $input);

            $valid = false;
        }

        return $valid;
    }

    protected function getMappedAttributes($attributes)
    {
        $attr = [];

        $map = $this->map;

        foreach ($attributes as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }
}
