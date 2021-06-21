<?php


namespace Unit\Models\Merchant\Escalations\Actions\Handlers;

use DB;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Escalations;
use RZP\Jobs\MerchantEscalationAction;

use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class DisablePaymentsHandlerTest extends TestCase
{
    use DbEntityFetchTrait;

    public function testDisablePaymentHandler()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);

        $merchant = $this->fixtures->create('merchant', [
            'activated' => 1,
            'live'      => true
        ]);
        $merchantId = $merchant->getId();

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchantId,
            'activation_status' => 'instantly_activated'
        ]);

        $escalation = $this->fixtures->on('live')->create('merchant_onboarding_escalations', [
            'merchant_id'   => $merchantId,
            'type'          => Escalations\Constants::PAYMENT_BREACH,
            'milestone'     => 'L1',
            'amount'        => 500000,
            'threshold'     => 500000
        ]);

        $action = $this->fixtures->on('live')->create('onboarding_escalation_actions', [
            'escalation_id'  => $escalation->getId(),
            'action_handler' => Escalations\Actions\Handlers\DisablePaymentsHandler::class,
            'status'         => 'pending'
        ]);

        // dispatch action
        MerchantEscalationAction::dispatch(
            $merchantId, $action->getId(), Escalations\Actions\Handlers\DisablePaymentsHandler::class, []);

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertFalse($merchant->getAttribute('live'));
        $this->assertEquals(0, $merchant->getAttribute('activated'));
    }
}
