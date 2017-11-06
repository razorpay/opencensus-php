<?php

namespace RZP\Models\BharatQr;

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
        $bharatQr = (new Entity)->build($input);

        return $bharatQr;
    }

    public function processPayment(array $input)
    {
        $input = $this->getBharatQrInputParams($input);

        try
        {
            $bharatQr = $this->create($input);

            // @todo hande failed Payment
            if (empty($bharatQr->getProviderReferenceId()) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PROVIDER_REFERENCE_ID_NEEDS_TO_SENT);
            }

            $this->mutex->acquireAndRelease(
                $input[Entity::MERCHANT_REFERENCE],
                function() use ($bharatQr)
                {
                    (new Processor)->process($bharatQr);
                },
                60,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

            $valid = true;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex, Trace::ERROR, TraceCode::BHARAT_QR_PAYMENT_PROCESSING_FAILED, $input);

            $valid = false;
        }

        return $valid;
    }


    protected function getBharatQrInputParams(array $input)
    {
        $defaultInput = [
            Entity::METHOD   => Method::CARD,
        ];

        $input = $this->getMappedAttributes($input);

        //As the amount sent by hitachi notification is string with format 1.00
        $input[Entity::AMOUNT] = (int) ($input[Entity::AMOUNT] * 100);

        return array_merge($defaultInput, $input);
    }

    protected function getMappedAttributes(array $attributes)
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
