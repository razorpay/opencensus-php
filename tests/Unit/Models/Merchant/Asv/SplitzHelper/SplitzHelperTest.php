<?php

namespace Unit\Models\Merchant\Asv\SplitzHelper;

use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;

class SplitzHelperTest extends TestCase
{

    public function testIsSplitzOn()
    {
        #T1 starts - isSplitzOn - True
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('info');
        $traceMock->expects($this->never())->method('traceException');
        $output = [
            'status_code' => 200,
            'response' => [
                'id' => '10000000000000',
                'project_id' => 'K1ZCHBSn7hbCMN',
                'experiment' => [
                    'id' => 'K1ZaAGS9JfAUHj',
                    'name' => 'CallSyncDviationAPI',
                    'exclusion_group_id' => '',
                ],
                'variant' => [
                    'id' => 'K1ZaAHZ7Lnumc6',
                    'name' => 'Dummy Enabled',
                    'variables' => [
                        [
                            'key' => 'enabled',
                            'value' => 'true',
                        ]
                    ],
                    'experiment_id' => 'K1ZaAGS9JfAUHj',
                    'weight' => 100,
                    'is_default' => false
                ],
                'Reason' => 'bucketer',
                'steps' => [
                    'sampler',
                    'exclusion',
                    'audience',
                    'assign_bucket'
                ]
            ]
        ];

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($output);
        $splitzHelper = new SplitzHelper();
        $output = $splitzHelper->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000', []);
        $this->assertTrue($output);
        #T1 ends

        #T2 starts - isSplitzOn - False - status code 400
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('info');
        $traceMock->expects($this->never())->method('traceException');
        $output = [
            'status_code' => 400,
            'response' => [
            ]
        ];

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($output);
        $splitzHelper = new SplitzHelper();
        $output = $splitzHelper->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
        $this->assertFalse($output);
        #T2 ends

        #T3 starts - isSplitzOn - False - status code 200
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(2))->method('info');
        $traceMock->expects($this->never())->method('traceException');
        $output = [
            'status_code' => 200,
            'response' => [
                'id' => '10000000000000',
                'project_id' => '',
                'experiment' => [
                    'id' => 'K1ZaAGS9JfAUHj',
                    'name' => '',
                    'exclusion_group_id' => '',
                ],
                'variant' => null,
                'Reason' => '',
                'steps' => []
            ]
        ];

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($output);
        $splitzHelper = new SplitzHelper();
        $output = $splitzHelper->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
        $this->assertFalse($output);
        #T3 ends

        #T4 starts - isSplitzOn - False - Exception occurred while evaluating splitz
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('info');
        $traceMock->expects($this->exactly(1))->method('traceException');
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')
            ->willThrowException(new \Exception('some error encountered while calling splitz'));
        $splitzHelper = new SplitzHelper();
        $output = $splitzHelper->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
        $this->assertFalse($output);
        #T4 ends
    }

    protected function createTraceMock()
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }

    protected function createSplitzMock(array $methods = ['evaluateRequest'])
    {

        $splitzMock = $this->getMockBuilder(SplitzService::class)
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('splitzService', $splitzMock);

        return $splitzMock;
    }
}
