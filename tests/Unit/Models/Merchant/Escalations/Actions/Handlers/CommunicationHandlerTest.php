<?php


namespace Unit\Models\Merchant\Escalations\Actions\Handlers;

use DB;
use Mail;
use Queue;
use RZP\Constants\Mode;
use RZP\Jobs\MerchantEscalationAction;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Notifications\Onboarding\Events;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Escalations;

class CommunicationHandlerTest extends TestCase
{
    public function testCommunicationSentForEvent()
    {
        Mail::fake();

        $this->app->instance("rzp.mode", Mode::LIVE);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');
        $merchantId = $merchantDetail->getMerchantId();

        $escalation = $this->fixtures->on('live')->create('merchant_onboarding_escalations', [
            'merchant_id'   => $merchantId,
            'type'          => Escalations\Constants::PAYMENT_BREACH,
            'milestone'     => 'L1',
            'amount'        => 500000,
            'threshold'     => 500000
        ]);

        $action = $this->fixtures->on('live')->create('onboarding_escalation_actions', [
            'escalation_id'  => $escalation->getId(),
            'action_handler' => Escalations\Actions\Handlers\CommunicationHandler::class,
            'status'         => 'pending'
        ]);

        $params = [
            'event' => Events::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED
        ];

        MerchantEscalationAction::dispatch(
            $merchantId, $action->getId(), Escalations\Actions\Handlers\CommunicationHandler::class, $params);

        //verify email has been sent
        Mail::assertQueued(MerchantOnboardingEmail::class, function ($mail) {
            $viewData = $mail->viewData;

            $this->assertEquals('emails.merchant.onboarding.payments_breach_blocked', $mail->view);

            return true;
        });
    }
}
