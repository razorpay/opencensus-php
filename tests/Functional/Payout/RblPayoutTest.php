<?php

namespace RZP\Tests\Functional\Payout;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class RblPayoutTest extends TestCase
{
    use PayoutTrait;
    use PaymentTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        parent::setUp();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000, 'direct', 'rbl');

        $this->app['cache']->flush();

        $this->ba->privateAuth();
    }

    public function testCreatingPendingPayoutsForRblWithUnsupportedModeChannelDestinationTypeCombo()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createWorkflowFeature();

        $workflow = $this->createWorkflow([
          'org_id'      => '100000razorpay',
          'name'        => 'some workflow',
          'permissions' => ['create_payout'],
        ]);

        $attributes = [
            'merchant_id' => '10000000000000',
            'min_amount'  => 0,
            'max_amount'  => 1000000,
            'workflow_id' => $workflow->getId(),
        ];

        $this->fixtures->create('workflow_payout_amount_rules', $attributes);

        $this->createVpaFundAccount(['id' => 'D6XkDQaM3whg5v']);

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testCreatingPendingPayoutsForRblWithSupportedModeChannelDestinationTypeCombo()
    {
        $this->liveSetUp();
        $this->setupWorkflowForLiveMode();
        $this->disableWorkflowMocks();

        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        Carbon::setTestNow();
    }
}
