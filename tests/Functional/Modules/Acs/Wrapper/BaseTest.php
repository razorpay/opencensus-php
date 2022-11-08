<?php

namespace RZP\Tests\Functional\Modules\Acs\Wrapper;

use Config;
use RZP\Services\SplitzService;
use RZP\Modules\Acs\Wrapper\Base;
use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;

class BaseTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    public function testEvaluateConfig()
    {
        #T1 starts -  (Enabled - False) - evaluated False
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['isSplitzOn']);
        $mockedBaseWrapper->expects($this->never())->method('isSplitzOn');
        $config = ['enabled' => false, 'full_enabled' => false, 'splitz_experiment_id' => ''];
        $evaluatedResult = $mockedBaseWrapper->evaluateConfig('10000000000000', $config, []);
        $this->assertFalse($evaluatedResult);
        #T1 ends


        #T2 starts -  (Enabled - True, FullEnabled - False, splitz experiment id empty) - evaluated False
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['isSplitzOn']);
        $mockedBaseWrapper->expects($this->never())->method('isSplitzOn');
        $config = ['enabled' => true, 'full_enabled' => false, 'splitz_experiment_id' => ''];
        $evaluatedResult = $mockedBaseWrapper->evaluateConfig('10000000000000', $config, []);
        $this->assertFalse($evaluatedResult);
        #T2 ends

        #T3 starts -  (Enabled - True and FullEnabled - False,  splitz experiment id defined) - evaluated False
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(false);
        $config = ['enabled' => true, 'full_enabled' => false, 'splitz_experiment_id' => '1000000000000x'];
        $evaluatedResult = $mockedBaseWrapper->evaluateConfig('10000000000000', $config, []);
        $this->assertFalse($evaluatedResult);
        #T3 ends

        #T4 starts -  (Enabled - True and FullEnabled - False,  splitz experiment id defined) - evaluated True
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);
        $config = ['enabled' => true, 'full_enabled' => false, 'splitz_experiment_id' => '1000000000000x'];
        $evaluatedResult = $mockedBaseWrapper->evaluateConfig('10000000000000', $config, []);
        $this->assertTrue($evaluatedResult);
        #T4 ends

        #T5 starts -  (Enabled - True and FullEnabled - True,  splitz experiment id defined) - evaluated True
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['isSplitzOn']);
        $mockedBaseWrapper->expects($this->never())->method('isSplitzOn');
        $config = ['enabled' => true, 'full_enabled' => true, 'splitz_experiment_id' => '1000000000000x'];
        $evaluatedResult = $mockedBaseWrapper->evaluateConfig('10000000000000', $config, []);
        $this->assertTrue($evaluatedResult);
        #T5 ends
    }


    public function testIsWriteShadowOn()
    {
        $allRouteOrJobNameWriteMigrationConfigEnabled = [
            'write' => [
                'enabled' => true,
                'full_enabled' => false,
                'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
            ]
        ];

        $allRouteOrJobNameWriteMigrationConfigDiabled = [
            'write' => [
                'enabled' => false,
                'full_enabled' => false,
                'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
            ]
        ];

        $bvsValidationJobWriteMigrationConfigEnabled = [
            'write' => [
                'enabled' => true,
                'full_enabled' => false,
                'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
            ]
        ];

        $bvsValidationJobWriteMigrationConfigFullEnabled = [
            'write' => [
                'enabled' => true,
                'full_enabled' => true,
                'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
            ]
        ];

        $bvsValidationJobWriteMigrationConfigDisabled = [
            'write' => [
                'enabled' => false,
                'full_enabled' => false,
                'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
            ]
        ];

        #T1 starts - All Route Or Job Name Migration Config Enabled - Shadow On
        Config::set('asv_migration', ['all_route_or_job' => $allRouteOrJobNameWriteMigrationConfigEnabled]);

        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);
        $isWriteShadowOn = $mockedBaseWrapper->isWriteShadowOn('10000000000000');
        $this->assertTrue($isWriteShadowOn);
        #T1 ends

        #T2 starts - All Route Or Job Name Migration Config Enabled - Shadow Off
        Config::set('asv_migration', ['all_route_or_job' => $allRouteOrJobNameWriteMigrationConfigEnabled]);

        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(false);
        $isWriteShadowOn = $mockedBaseWrapper->isWriteShadowOn('10000000000000');
        $this->assertFalse($isWriteShadowOn);
        #T2 ends

        #T3 starts - All Route Or Job Name Migration Config Disabled Evaluate Using Specific config Enabled - Splitz True  - Shadow On
        Config::set('asv_migration', ['all_route_or_job' => $allRouteOrJobNameWriteMigrationConfigDiabled, 'bvs_validation_job' => $bvsValidationJobWriteMigrationConfigEnabled]);
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);
        $isWriteShadowOn = $mockedBaseWrapper->isWriteShadowOn('10000000000000');
        $this->assertTrue($isWriteShadowOn);
        #T3 ends

        #T4 starts - All Route Or Job Name Migration Config Disabled Evaluate Using Specific config Disabled - Shadow Off
        Config::set('asv_migration', ['all_route_or_job' => $allRouteOrJobNameWriteMigrationConfigDiabled, 'bvs_validation_job' => $bvsValidationJobWriteMigrationConfigDisabled]);
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->never())->method('isSplitzOn');
        $isWriteShadowOn = $mockedBaseWrapper->isWriteShadowOn('10000000000000');
        $this->assertFalse($isWriteShadowOn);
        #T4 ends


        #T5 starts - All Route Or Job Name Migration Config Disabled Evaluate Using Specific config Full Enabled - Shadow On
        Config::set('asv_migration', ['all_route_or_job' => $allRouteOrJobNameWriteMigrationConfigDiabled, 'bvs_validation_job' => $bvsValidationJobWriteMigrationConfigFullEnabled]);
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->never())->method('isSplitzOn');
        $isWriteShadowOn = $mockedBaseWrapper->isWriteShadowOn('10000000000000');
        $this->assertTrue($isWriteShadowOn);
        #T5 ends
    }

    public function testIsWriteShadowOrReverseShadowOn()
    {
        $allRouteOrJobNameReadShadowMigrationConfigEnabled = [
            'read' => [
                'shadow' => [
                    'enabled' => true,
                    'full_enabled' => false,
                    'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
                ]
            ]];


        $bvsValidationJobReadShadowMigrationConfigEnabled = [
            'read' => [
                'shadow' => [
                    'enabled' => true,
                    'full_enabled' => false,
                    'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
                ]
            ]
        ];


        $bvsValidationJobReadShadowMigrationConfigFullEnabled = [
            'read' => [
                'shadow' => [
                    'enabled' => true,
                    'full_enabled' => true,
                    'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
                ]
            ]
        ];


        $allRouteOrJobNameReadReverseShadowMigrationConfigEnabled = [
            'read' => [
                'reverse_shadow' => [
                    'enabled' => true,
                    'full_enabled' => false,
                    'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
                ]
            ]];


        $bvsValidationJobReadReverseShadowMigrationConfigEnabled = [
            'read' => [
                'reverse_shadow' => [
                    'enabled' => true,
                    'full_enabled' => false,
                    'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
                ]
            ]
        ];


        $bvsValidationJobReadReverseShadowMigrationConfigFullEnabled = [
            'read' => [
                'reverse_shadow' => [
                    'enabled' => true,
                    'full_enabled' => true,
                    'splitz_experiment_id' => 'K1ZaAGS9JfAUHj'
                ]
            ]
        ];

        #T1 starts - All Route Or Job Name Migration Config Enabled - Shadow On
        Config::set('asv_migration', ['all_route_or_job' => $allRouteOrJobNameReadShadowMigrationConfigEnabled]);

        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);
        $isWriteShadowOn = $mockedBaseWrapper->isReadShadowOrReverseShadowOn('10000000000000', 'shadow');
        $this->assertTrue($isWriteShadowOn);
        #T1 ends


        #T2 starts - All Route Or Job Name Migration Config Disabled Evaluate Using Specific config Enabled - Splitz True  - Shadow On
        Config::set('asv_migration', ['bvs_validation_job' => $bvsValidationJobReadShadowMigrationConfigEnabled]);
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);
        $isWriteShadowOn = $mockedBaseWrapper->isReadShadowOrReverseShadowOn('10000000000000', 'shadow');
        $this->assertTrue($isWriteShadowOn);
        #T2 ends

        #T3 starts - All Route Or Job Name Migration Config Disabled Evaluate Using Specific config Enabled - Splitz False  - Shadow Off
        Config::set('asv_migration', ['bvs_validation_job' => $bvsValidationJobReadShadowMigrationConfigEnabled]);
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(false);
        $isWriteShadowOn = $mockedBaseWrapper->isReadShadowOrReverseShadowOn('10000000000000', 'shadow');
        $this->assertFalse($isWriteShadowOn);
        #T3 ends

        #T4 starts - All Route Or Job Name Migration Config Disabled Evaluate Using Specific config Full Enabled - Shadow On
        Config::set('asv_migration', ['bvs_validation_job' => $bvsValidationJobReadShadowMigrationConfigFullEnabled]);
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->never())->method('isSplitzOn');
        $isWriteShadowOn = $mockedBaseWrapper->isReadShadowOrReverseShadowOn('10000000000000', 'shadow');
        $this->assertTrue($isWriteShadowOn);
        #T4 ends


        #T5 starts - All Route Or Job Name Migration Config Enabled - ReverseShadow On
        Config::set('asv_migration', ['all_route_or_job' => $allRouteOrJobNameReadReverseShadowMigrationConfigEnabled]);

        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);
        $isWriteShadowOn = $mockedBaseWrapper->isReadShadowOrReverseShadowOn('10000000000000', 'reverse_shadow');
        $this->assertTrue($isWriteShadowOn);
        #T5 ends

        #T6 starts - All Route Or Job Name Migration Config Disabled Evaluate Using Specific config Enabled - Splitz True  - ReverseShadow On
        Config::set('asv_migration', ['bvs_validation_job' => $bvsValidationJobReadReverseShadowMigrationConfigEnabled]);
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(true);
        $isWriteShadowOn = $mockedBaseWrapper->isReadShadowOrReverseShadowOn('10000000000000', 'reverse_shadow');
        $this->assertTrue($isWriteShadowOn);
        #T6 ends

        #T7 starts - All Route Or Job Name Migration Config Disabled Evaluate Using Specific config Enabled - Splitz False  - Reverse Shadow Off
        Config::set('asv_migration', ['bvs_validation_job' => $bvsValidationJobReadReverseShadowMigrationConfigEnabled]);
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->exactly(1))->method('isSplitzOn')->willReturn(false);
        $isWriteShadowOn = $mockedBaseWrapper->isReadShadowOrReverseShadowOn('10000000000000', 'reverse_shadow');
        $this->assertFalse($isWriteShadowOn);
        #T7 ends

        #T8 starts - All Route Or Job Name Migration Config Disabled Evaluate Using Specific config Full Enabled - Reverse Shadow On
        Config::set('asv_migration', ['bvs_validation_job' => $bvsValidationJobReadReverseShadowMigrationConfigFullEnabled]);
        $mockedBaseWrapper = $this->getMockedBaseWrapper(['getRouteOrJobName', 'isSplitzOn']);
        $mockedBaseWrapper->expects($this->exactly(1))->method('getRouteOrJobName')->willReturn('bvs_validation_job');
        $mockedBaseWrapper->expects($this->never())->method('isSplitzOn');
        $isWriteShadowOn = $mockedBaseWrapper->isReadShadowOrReverseShadowOn('10000000000000', 'reverse_shadow');
        $this->assertTrue($isWriteShadowOn);
        #T8 ends
    }

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
                    'name' => 'SyncDeviation Enabled',
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
        $mockedBaseWrapper = $this->getMockedBaseWrapper();
        $output = $mockedBaseWrapper->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000', []);
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
        $mockedBaseWrapper = $this->getMockedBaseWrapper();
        $output = $mockedBaseWrapper->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
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
        $mockedBaseWrapper = $this->getMockedBaseWrapper();
        $output = $mockedBaseWrapper->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
        $this->assertFalse($output);
        #T3 ends

        #T4 starts - isSplitzOn - False - Exception occurred while evaluating splitz
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('info');
        $traceMock->expects($this->exactly(1))->method('traceException');
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')
            ->willThrowException(new \Exception('some error encountered while calling splitz'));
        $mockedBaseWrapper = $this->getMockedBaseWrapper();
        $output = $mockedBaseWrapper->isSplitzOn('K1ZaAGS9JfAUHj', '10000000000000');
        $this->assertFalse($output);
        #T4 ends
    }

    protected function getMockedBaseWrapper($methods = [])
    {
        $baseWrapper = $this->getMockBuilder(Base::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
        return $baseWrapper;
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
