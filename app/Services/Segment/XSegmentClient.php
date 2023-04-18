<?php


namespace RZP\Services\Segment;

use RZP\Models\Merchant;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Trace\TraceCode;

class XSegmentClient extends SegmentAnalyticsClient
{

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('services.x-segment');
    }

    public function sendEventToSegment(string $eventName, Merchant\Entity $merchant = null, array $properties = []){
        try {

            $this->trace->info(TraceCode::XSEGMENT_EVENT_DISPATCH,
                [
                    'merchant_id' => $merchant['id']?? null,
                    'user_id'     => $user['id']?? null,
                    'event_name'  => $eventName
                ]);

            if (empty($merchant) === true) {
                $merchant = $this->app['basicauth']->getMerchant();
            }

            if(empty($merchant) === false) {
                $user = $this->app['basicauth']->getUser() ?? $merchant->users()->first();
            }

            if (empty($user) === false and empty($merchant) === false) {

                $result = 'on';

                if($eventName === SegmentEvent::CONTACT_CREATED or $eventName === SegmentEvent::CA_PAYOUT_PROCESSED
                    or $eventName === SegmentEvent::VA_PAYOUT_PROCESSED or $eventName === SegmentEvent::FUND_ACCOUNT_ADDED){
                    $variant = $this->app['razorx']->getTreatment($merchant->getId(),
                        Merchant\RazorxTreatment::SEND_EVENTS_TO_SEGMENT,
                        $this->mode
                    );
                    $result = $variant;
                }

                if($result != 'off') {
                    $this->pushIdentifyandTrackEvent($merchant, $properties, $eventName);
                }
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->info(TraceCode::USER_FETCH_FAILED_FOR_SEGMENT_EVENT,
                [
                    'merchant_id' => $merchant['id']?? null,
                    'user_id'     => $user['id']?? null,
                    'event_name'  => $eventName,
                    'exception'   => $e,
                ]);
        }
    }
}
