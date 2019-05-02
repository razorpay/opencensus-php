<?php

namespace RZP\Trace;

use RZP\Trace\TraceCode;
use RZP\Models\P2p\Base\Libraries\Context;

class P2pTraceProcessor
{
    const NA    = 'na';
    /**
     * @var Context
     */
    protected $context;

    protected $processables = [
        TraceCode::P2P_REQUEST,
        TraceCode::P2P_RESPONSE,
        TraceCode::P2P_GATEWAY_REQUEST,
        TraceCode::P2P_GATEWAY_RESPONSE,
    ];

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function __invoke(array $record)
    {
        $record['p2p'] = [
            'handle'            => $this->getHandleCode() ?? self::NA,
            'request_id'        => $this->getRequestId() ?? self::NA,
            'merchant_id'       => $this->getMerchantId() ?? self::NA,
            'device_id'         => $this->getDeviceId() ?? self::NA,
            'device_token_id'   => $this->getDeviceTokenId() ?? self::NA,
        ];

        $message = $record['message'] ?? null;

        if (in_array($message, $this->processables, true))
        {
            $record['context'] = $this->processTrace($record['context']);
        }

        return $record;
    }

    public function getMerchantId()
    {
        if ($this->context->isContextMerchant())
        {
            return $this->context->getMerchant()->getId();
        }
    }

    public function getDeviceId()
    {
        if ($this->context->isContextDevice())
        {
            return $this->context->getDevice()->getId();
        }
    }

    public function getHandleCode()
    {
        if ($this->context->isContextMerchant())
        {
            return $this->context->handleCode();
        }
    }

    public function getDeviceTokenId()
    {
        if ($this->context->isContextDevice())
        {
            return $this->context->getDeviceToken()->getId();
        }
    }

    public function getRequestId()
    {
        return $this->context->getRequestId();
    }

    protected function processTrace(array $input)
    {
        $entity     = $input['entity'] ?? self::NA;
        $action     = $input['action'] ?? self::NA;
        $gateway    = $input['gateway'] ?? self::NA;

        unset($input['entity'], $input['action'], $input['gateway']);

        $output = [
            'entity'    => $entity,
            'action'    => $action,
            'gateway'   => $gateway,
            $gateway    => [
                $entity => [
                    $action => [

                    ]
                ],
            ],
        ];

        foreach ($input as $key => $value)
        {
            $output[$gateway][$entity][$action][$key] = $value;
        }

        return $output;
    }
}
