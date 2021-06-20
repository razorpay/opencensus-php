<?php

namespace RZP\Models\P2p\Base\Metrics;

use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\P2p\Base\Libraries\Context;

class GatewayActionMetric extends Metric
{
    // Name of the metric
    const PSP_GATEWAY_ACTION_TOTAL     = 'psp_gateway_action_total';

    // Dimensions
    const DIMENSION_ENTITY              = 'entity';
    const DIMENSION_ACTION              = 'action';
    const DIMENSION_GATEWAY             = 'gateway';
    const DIMENSION_OS                  = 'os';
    const DIMENSION_OS_VERSION          = 'os_version';
    const DIMENSION_NETWORK_TYPE        = 'network_type';
    const DIMENSION_SDK_VERSION         = 'sdk_version';
    const DIMENSION_STATUS              = 'status';
    const DIMENSION_TYPE                = 'type';

    // Status
    const SUCCESS                       = 'success';
    const FAILED                        = 'failed';

    // Response type
    const NEXT                          = 'next';
    const PROCESSED                     = 'processed';

    protected $dimensions;

    public function __construct(Context $context)
    {
        $this->dimensions = [
            self::DIMENSION_ENTITY       => null,
            self::DIMENSION_ACTION       => null,
            self::DIMENSION_GATEWAY      => null,
            self::DIMENSION_OS           => $this->getContextMetaValue($context, Context::OS),
            self::DIMENSION_OS_VERSION   => $this->getContextMetaValue($context, Context::OS_VERSION),
            self::DIMENSION_NETWORK_TYPE => $this->getContextMetaValue($context, Context::NETWORK_TYPE),
            self::DIMENSION_SDK_VERSION  => $this->getContextMetaValue($context, Context::SDK_VERSION),
            self::DIMENSION_STATUS       => null,
            self::DIMENSION_TYPE         => null,
        ];
    }

    /**
     * @param string $gateway
     * @return GatewayActionMetric
     */
    public function setGateway(string $gateway): GatewayActionMetric
    {
        $this->dimensions[self::DIMENSION_GATEWAY] = $gateway;

        return $this;
    }

    /**
     * @param string $action
     * @return GatewayActionMetric
     */
    public function setAction(string $action): GatewayActionMetric
    {
        $this->dimensions[self::DIMENSION_ACTION] = $action;

        return $this;
    }

    /**
     * @param $entity string
     * @return GatewayActionMetric
     */
    public function setEntity($entity): GatewayActionMetric
    {
        $this->dimensions[self::DIMENSION_ENTITY] = $entity;

        return $this;
    }

    /**
     * Changes the dimension type to processed
     * @return GatewayActionMetric
     */
    public function typeProcessed(): GatewayActionMetric
    {
        $this->dimensions[self::DIMENSION_TYPE] = self::PROCESSED;

        return $this;
    }

    /**
     * Changes the dimension type to next
     * @return GatewayActionMetric
     */
    public function typeNext(): GatewayActionMetric
    {
        $this->dimensions[self::DIMENSION_TYPE] = self::NEXT;

        return $this;
    }

    /**
     * Changes the dimension type to success
     * @return GatewayActionMetric
     */
    public function statusSuccess(): GatewayActionMetric
    {
        $this->dimensions[self::DIMENSION_STATUS] = self::SUCCESS;

        return $this;
    }

    /**
     * Changes the dimension type to failed
     * @return $this
     */
    public function statusFailed()
    {
        $this->dimensions[self::DIMENSION_STATUS] = self::FAILED;

        return $this;
    }

    public function pushCount()
    {
        $this->count(self::PSP_GATEWAY_ACTION_TOTAL, $this->dimensions);

        return $this;
    }

    protected function getContextMetaValue(Context $context, string $key)
    {
        $meta = $context->getOptions()->get(Context::META);

        if($meta instanceof ArrayBag)
        {
            return $meta->get($key);
        }

        return null;
    }
}
