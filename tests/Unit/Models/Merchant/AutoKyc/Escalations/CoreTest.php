<?php


namespace Unit\Models\Merchant\AutoKyc\Escalations;


use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\AutoKyc\Escalations\Core as EscalationCore;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants as EscalationConstant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class CoreTest extends TestCase
{
    use DbEntityFetchTrait;

    /**
     * Scenario: T+2 days after hard limit breach
     * Expectation: 3rd Escalation should be raised and merchant should put in FOH. Live should be enabled.
     */
    public function testHardLimitEscalation2()
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
            'escalation_level'  => 2,
            'escalation_method' => 'email',
            'escalation_type'   => 'hard_limit',
            'created_at'        => Carbon::now(Timezone::IST)->subDays(2)->getTimestamp()
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
