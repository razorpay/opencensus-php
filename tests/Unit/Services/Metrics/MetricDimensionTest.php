<?php

namespace RZP\Tests\Unit\Services\Metrics;

use Config;
use RZP\Tests\TestCase;
use RZP\Constants\Metric;
use RZP\Trace\Metrics\DimensionsProcessor;

class MetricDimensionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sets up a sample whitelisted values for specific labels
        Config::set(
            'metrics.whitelisted_label_values',
            [
                Metric::LABEL_RZP_KEY_ID          => [
                    'key_0000000001',
                    'key_0000000002',
                ],

                Metric::LABEL_RZP_MERCHANT_ID     => [
                    'merchant_00001',
                ],

                Metric::LABEL_RZP_OAUTH_CLIENT_ID => [
                    'oauth_00000001',
                    'oauth_00000002',
                ],
            ]);
    }

    /**
     * @dataProvider getModifiedDimensionsTestCaseProvider
     */
    public function testGetModifiedDimensions(array $dimensions, array $expected)
    {
        $actual = (new DimensionsProcessor)->process($dimensions);

        $this->assertEquals($expected, $actual);
    }

    public function getModifiedDimensionsTestCaseProvider(): array
    {
        return require_once __DIR__ . '/helpers/MetricDimensionTestData.php';
    }
}
