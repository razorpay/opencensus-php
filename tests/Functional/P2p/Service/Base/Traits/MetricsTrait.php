<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use RZP\Tests\Functional\Helpers\MocksMetricTrait;
use RZP\Models\P2p\Base\Metrics\TransactionMetric;
use RZP\Models\P2p\Base\Metrics\GatewayActionMetric;
use RZP\Tests\Unit\Mock\Metric\Driver as MockMetricDriver;

trait MetricsTrait
{
    use MocksMetricTrait;

    /**
     * @var $mock MockMetricDriver
     */
    protected $mock;

    protected function mockMetric()
    {
        $this->mock = $this->mockMetricDriver('mock');
    }

    protected function assertCountMetric(string $metricName, $override = [], int $index = 0)
    {
        $default = $this->getDefaultMetricValues($metricName);

        $expected = array_merge($default, $override);

        $metrics = $this->mock->metric($metricName);

        $actual = $metrics[$index];

        $this->assertArraySubset($expected, $actual);
    }

    protected function getDefaultMetricValues(string $metricName)
    {
        switch ($metricName)
        {
            case TransactionMetric::PSP_TRANSACTION_TOTAL:
                return [
                    TransactionMetric::DIMENSION_MODE               => 'default',
                    TransactionMetric::DIMENSION_TYPE               => 'collect',
                    TransactionMetric::DIMENSION_FLOW               => 'credit',
                    TransactionMetric::DIMENSION_STATUS             => 'created',
                    TransactionMetric::DIMENSION_GATEWAY            => 'p2p_upi_axis',
                    TransactionMetric::DIMENSION_ERROR_CODE         =>  null,
                    TransactionMetric::DIMENSION_PAYEE_TYPE         => 'vpa',
                    TransactionMetric::DIMENSION_PAYER_TYPE         => 'vpa',
                    TransactionMetric::DIMENSION_MERCHANT_ID        => '10000000000000',
                    TransactionMetric::DIMENSION_PREVIOUS_STATUS    => 'created',
                    TransactionMetric::DIMENSION_IS_SELF_TRANSFER   => false,
                ];

            case GatewayActionMetric::PSP_GATEWAY_ACTION_TOTAL:
                return [
                    GatewayActionMetric::DIMENSION_ACTION       => 'verification',
                    GatewayActionMetric::DIMENSION_ENTITY       => 'device',
                    GatewayActionMetric::DIMENSION_GATEWAY      => 'p2p_upi_axis',
                    GatewayActionMetric::DIMENSION_STATUS       => 'success',
                    GatewayActionMetric::DIMENSION_TYPE         => 'processed',
                    GatewayActionMetric::DIMENSION_OS           =>  null,
                    GatewayActionMetric::DIMENSION_OS_VERSION   =>  null,
                    GatewayActionMetric::DIMENSION_SDK_VERSION  =>  null,
                    GatewayActionMetric::DIMENSION_NETWORK_TYPE =>  null
                ];
        }
    }
}
