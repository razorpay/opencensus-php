<?php

namespace RZP\Trace;

use RZP\Models\P2p\Base\Libraries\Context;

class P2pTraceProcessor
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function __invoke(array $record)
    {
        $record['p2p'] = [
            'handle'            => $this->getHandleCode(),
            'request_id'        => $this->getRequestId(),
            'merchant_id'       => $this->getMerchantId(),
            'device_id'         => $this->getDeviceId(),
            'device_token_id'   => $this->getDeviceTokenId(),
        ];

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
}
