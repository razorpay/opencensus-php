<?php

namespace RZP\Models\Qr;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Method;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Provider;

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

        return $qr;
    }

    public function processPayment(array $input)
    {
        $defaultInput = [
            Entity::PROVIDER => Provider::BHARAT_QR,
            Entity::METHOD   => Method::CARD,
        ];

        $input = $this->getMappedAttributes($input);

        $input = array_merge($defaultInput, $input);

        try
        {
            $qr = $this->create($input);


                    (new Processor)->process($qr);


            $valid = true;
        }
        catch (Exception\BadRequestValidationFailureException $ex)
        {
            sd($ex->getMessage());
            $this->trace->traceException(
                $ex, Trace::ERROR, TraceCode::QR_PAYMENT_PROCESSING_FAILED, $input);

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
