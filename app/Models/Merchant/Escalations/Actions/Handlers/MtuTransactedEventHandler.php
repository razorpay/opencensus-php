<?php


namespace RZP\Models\Merchant\Escalations\Actions\Handlers;


use Carbon\Carbon;
use RZP\Models\Merchant\Escalations\Actions\Entity;
use RZP\Models\Merchant\Escalations\Utils;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\Escalations\Actions\Constants as ActionConstant;

class MtuTransactedEventHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        // If existing successful action is found then just return it.
        // It means we've already sent MTU event to segment
        $existingSuccessAction = $this->repo->onboarding_escalation_actions->fetchActionWithHandlerAndStatus(
            Utils::getClassShortName(MtuTransactedEventHandler::class), ActionConstant::SUCCESS
        );

        if(empty($existingSuccessAction) === false)
        {
            return;
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $properties = [
            'mtu'                           => true,
            'first_transaction_timestamp'   => Carbon::now()->getTimestamp()
        ];

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::MTU_TRANSACTED);

        $this->app['segment-analytics']->buildRequestAndSend();
    }
}
