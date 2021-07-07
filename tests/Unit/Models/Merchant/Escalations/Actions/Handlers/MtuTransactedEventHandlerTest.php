<?php


namespace Unit\Models\Merchant\Escalations\Actions\Handlers;


use RZP\Constants\Mode;
use RZP\Models\Merchant\Escalations;
use RZP\Jobs\MerchantEscalationAction;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Notifications\Onboarding\Events;
use RZP\Services\RazorXClient;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Escalations\Actions\Handlers\MtuTransactedEventHandler;

class MtuTransactedEventHandlerTest extends TestCase
{
    private function createandFetchMocks()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app['razorx']->method('getTreatment')
            ->willReturn('on');

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        return [$segmentMock];
    }

    public function testFirstTimeSendingSegmentEvent()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');
        $merchantId = $merchantDetail->getMerchantId();

        $escalation = $this->fixtures->on('live')->create('merchant_onboarding_escalations', [
            'merchant_id'   => $merchantId,
            'type'          => Escalations\Constants::PAYMENT_BREACH,
            'milestone'     => 'L1',
            'amount'        => 100000,
            'threshold'     => 100000
        ]);

        $action = $this->fixtures->on('live')->create('onboarding_escalation_actions', [
            'escalation_id'  => $escalation->getId(),
            'action_handler' => MtuTransactedEventHandler::class,
            'status'         => 'pending'
        ]);

        [$segmentMock] = $this->createandFetchMocks();

        $segmentMock->expects($this->once())->method('pushIdentifyAndTrackEvent')->willReturn(true);

        MerchantEscalationAction::dispatch(
            $merchantId, $action->getId(), MtuTransactedEventHandler::class, []);
    }

    public function testSegmentEventNotSentSecondTime()
    {
        $this->app->instance("rzp.mode", Mode::LIVE);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');
        $merchantId = $merchantDetail->getMerchantId();

        $escalation = $this->fixtures->on('live')->create('merchant_onboarding_escalations', [
            'merchant_id'   => $merchantId,
            'type'          => Escalations\Constants::PAYMENT_BREACH,
            'milestone'     => 'L1',
            'amount'        => 100000,
            'threshold'     => 100000
        ]);

        // Existing action with success status
        $this->fixtures->on('live')->create('onboarding_escalation_actions', [
            'escalation_id'  => $escalation->getId(),
            'action_handler' => Escalations\Utils::getClassShortName(MtuTransactedEventHandler::class),
            'status'         => 'success'
        ]);

        $action = $this->fixtures->on('live')->create('onboarding_escalation_actions', [
            'escalation_id'  => $escalation->getId(),
            'action_handler' => Escalations\Utils::getClassShortName(MtuTransactedEventHandler::class),
            'status'         => 'pending'
        ]);

        [$segmentMock] = $this->createandFetchMocks();

        $segmentMock->expects($this->never())->method('pushIdentifyAndTrackEvent')->willReturn(true);

        MerchantEscalationAction::dispatch(
            $merchantId, $action->getId(), MtuTransactedEventHandler::class, []);
    }
}
