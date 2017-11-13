<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;

class Service extends Base\Service
{
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

    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function processPayment(array $input)
    {
        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST,
            $input
        );

        $bharatQrInputParams = $this->getBharatQrInputParams($input);

        $valid = $this->core->processPayment($bharatQrInputParams);

        $response = $this->getResponse($valid);

        return $response;
    }

    protected function getResponse(bool $valid)
    {
        if ($valid === true)
        {
            $xml = '<RESPONSE>OK</RESPONSE>';
        }
        else
        {
            $xml = '<RESPONSE>NOK</RESPONSE>';
        }

        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
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

    protected function getBharatQrInputParams(array $input)
    {
        $input = $this->getMappedAttributes($input);

        // For now notification only comes for card method
        $input[Entity::METHOD] = Method::CARD;

        return $input;
    }
}
