<?php

namespace RZP\Tests\Unit\XPayroll;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;

class XPayrollTest extends TestCase
{
    use TestsBusinessBanking;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/XPayrollTestData.php';
        parent::setUp();
        $this->setUpMerchantForBusinessBanking(false, 10000000);
    }

    public function testXPayrollUpdateIsCalledWhenFeatureIsEnabled()
    {
        $xPayrollServiceMock = Mockery::mock('RZP\Services\XPayroll\Service');

        $xPayrollServiceMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('xpayroll', $xPayrollServiceMock);

        $payout = $this->fixtures->create('payout', [
            'status'          => 'processed',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',

        ]);

        $this->fixtures->create('payout_source', [
            'payout_id'   => $payout->getId(),
            'source_id'   => 'xpayroll_1',
            'source_type' => 'xpayroll',
            'priority'    => 1
        ]);

        SourceUpdater::update($payout);

        //  assert that the Payout Update Status was called when source details for XPayroll are present
        $xPayrollServiceMock->shouldHaveReceived('pushPayoutStatusUpdate');
    }

    public function testXPayrollPushSkippedWhenSourceDetailsNotPresent()
    {
        $xPayrollServiceMock = Mockery::mock('RZP\Services\XPayroll\Service');

        $xPayrollServiceMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('xpayroll', $xPayrollServiceMock);

        $payout = $this->fixtures->create('payout', [
            'status'          => 'processed',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id'   => $payout->getId(),
            'source_id'   => 'pl_1',
            'source_type' => 'payout_links',
            'priority'    => 1
        ]);

        SourceUpdater::update($payout);

        //  assert that the Payout Update Status is not called when source details for XPayroll are not present

        $xPayrollServiceMock->shouldNotHaveReceived('pushPayoutStatusUpdate');
    }

    public function testGetBankingAccountsInternal()
    {
        $this->ba->appAuthTest($this->config['applications.xpayroll.secret']);
        $this->startTest();
    }

    public function testCreatePayout()
    {
        $this->ba->appAuthTest($this->config['applications.xpayroll.secret']);
        $this->startTest();
    }

    public function testStatusDetailsInXPayrollWebhook()
    {
        $payout = $this->fixtures->create('payout', [
            'status'           => 'processed',
            'pricing_rule_id'  => '1nvp2XPMmaRLxb',
            'status_details_id'=> '1nvp2XPMmaRLwb'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id'   => $payout->getId(),
            'source_id'   => 'xpr_1',
            'source_type' => 'xpayroll',
            'priority'    => 1
        ]);

        $this->fixtures->create('payouts_status_details', [
            'payout_id'   => $payout->getId(),
            'id'          => '1nvp2XPMmaRLwb',
            'status'      => 'processed',
            'reason'      => 'payout_processed',
            'description' => 'Payout is processed and the money has been credited into the beneficiaries account.',
            'mode'        => 'system',
        ]);

        $xPayrollServiceMock = Mockery::mock('RZP\Services\XPayroll\Service')->makePartial();

        $xPayrollServiceMock->shouldReceive('sendStatusUpdate')
            ->andReturnUsing(function(array $request) {
                $statusDetails = $request['status_details'];
                $statusDetailsId = $request['status_details_id'];

                $statusDetailsExpected = [
                    'reason'      => 'payout_processed',
                    'source'      => 'beneficiary_bank',
                    'description' => 'Payout is processed and the money has been credited into the beneficiaries account.',
                ];

                self::assertEquals('1nvp2XPMmaRLwb', $statusDetailsId);

                self::assertArraySubset($statusDetailsExpected, $statusDetails);

                return [];
            });

        $this->app->instance('xpayroll', $xPayrollServiceMock);

        SourceUpdater::update($payout);
    }
}
