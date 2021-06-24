<?php


namespace Unit\Models\Merchant\AutoKyc\Escalations;

use DB;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\AutoKyc\Escalations\Core as EscalationCore;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants as EscalationConstant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class CoreTest extends TestCase
{
    use DbEntityFetchTrait;

    private function createTransaction(string $merchantId, string $type, int $amount)
    {
        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'          => $type,
            'amount'        => $amount * 100,   // in paisa
            'merchant_id'   => $merchantId
        ]);
    }

    private function createAndFetchFixtures()
    {
        $permission = $this->fixtures->connection('live')->create('permission', [
            'name' => Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH_UNREGISTERED
        ]);

        // Creating workflow
        $workflow = $this->fixtures->connection('live')->create('workflow', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
            'name'   => "NC Workflow"
        ]);

        // Attaching create_payout permission to the workflow
        DB::connection('live')->table('workflow_permissions')->insert([
            'workflow_id'      => $workflow->getId(),
            'permission_id'    => $permission->getId()
        ]);
        DB::connection('live')->table('permission_map')->insert([
            'entity_id'     => OrgEntity::RAZORPAY_ORG_ID,
            'entity_type'   => 'org',
            'permission_id' => $permission->getId(),
        ]);

        $merchant = $this->fixtures->create('merchant', [
            'live'          => true,
            'activated'     => 1,
            'hold_funds'    => false
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_mcc_pending',
            'business_type'     => 11
        ]);

        return [$merchant];
    }

    public function testSoftLimitEscalation1()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId('100000razorpay');

        [$merchant] = $this->createAndFetchFixtures();

        $this->createTransaction($merchant->getId(), 'payment', 1000);

        (new EscalationCore())->handleSoftLimitBreach();

        $wf_action = $this->getDbLastEntity('workflow_action', 'live');

        self::assertNotEmpty($wf_action);

        $escalation = $this->getDbLastEntity('merchant_auto_kyc_escalations', 'live');

        self::assertNotEmpty($escalation);
        self::assertEquals($escalation->getAttribute('escalation_type'), 'soft_limit');
        self::assertEquals($escalation->getAttribute('escalation_level'), '1');
        self::assertEquals($escalation->getAttribute('workflow_id'), $wf_action->getId());
    }

    /**
     * Scenario: T+5 days after hard limit breach
     * Expectation: 4th Escalation should be raised and merchant should put in FOH. Live should be enabled.
     */
    public function testHardLimitEscalation4()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);

        $merchant = $this->fixtures->create('merchant', [
            'live'          => true,
            'activated'     => 1,
            'hold_funds'    => false
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_mcc_pending',
        ]);

        $this->fixtures->create('merchant_auto_kyc_escalations', [
            'merchant_id'       => $merchant['id'],
            'escalation_level'  => 3,
            'escalation_method' => 'email',
            'escalation_type'   => 'hard_limit',
            'created_at'        => Carbon::now(Timezone::IST)->subDays(5)->getTimestamp()
        ]);

        (new EscalationCore)->handleEscalationsCron();

        $merchant = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertTrue($merchant->getAttribute(MerchantEntity::LIVE));
        $this->assertEquals(1, $merchant->getAttribute(MerchantEntity::ACTIVATED));

        $this->assertTrue($merchant->getAttribute(MerchantEntity::HOLD_FUNDS));
        $this->assertEquals(
            EscalationConstant::HOLD_FUNDS_REASON_FOR_LIMIT_BREACH,
            $merchant->getAttribute(MerchantEntity::HOLD_FUNDS_REASON)
        );
    }
}
