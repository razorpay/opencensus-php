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
            'p2p_handle_acquirer'   => $this->getHandleAcquirer(),
            'handle'                => $this->getHandleCode(),
            'request_id'            => $this->getRequestId(),
            'merchant_id'           => $this->getMerchantId(),
            'device_id'             => $this->getDeviceId(),
            Context::DEVICE_META    => $this->getDeviceMeta(),
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
        if ($this->context->getHandle())
        {
            return $this->context->handleCode();
        }
    }

    public function getHandleAcquirer()
    {
        if ($this->context->getHandle())
        {
            return $this->context->getHandle()->getAcquirer();
        }
    }

    public function getDeviceContactNumber()
    {
        if ($this->context->isContextDevice())
        {
            return $this->context->getDevice()->getContact();
        }
    }

    public function getRequestId()
    {
        return $this->context->getRequestId();
    }

    public function getDeviceMeta()
    {
        $data = $this->context->getOptions()->get(Context::DEVICE_META);

        if (empty($data) === false)
        {
            return $data->toArray();
        }

        return [];
    }
}
