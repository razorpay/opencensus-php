<?php

namespace RZP\Models\Payout\TdsProcessor;

use App;
use Throwable;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\PayoutsDetails\Entity as PayoutDetailsEntity;

class Processor extends Base\Core
{
    public function processTds(PayoutEntity $payout)
    {
        /** @var PayoutDetailsEntity $payoutDetails */
        $payoutDetails = $payout->payoutsDetails()->first();

        if (empty($payoutDetails) === true)
        {
            $this->trace->info(TraceCode::NO_PAYOUT_DETAILS_FOR_PAYOUT, [
                'payout_id' => $payout->getPublicId(),
            ]);

            return;
        }

        $tdsCategoryId = $payoutDetails->getTdsCategoryId();

        if (empty($tdsCategoryId) === true)
        {
            $this->trace->info(TraceCode::NO_TDS_FOR_PAYOUT, [
                'payout_id' => $payout->getPublicId(),
            ]);

            return;
        }

        $additionalInfo = $payoutDetails->getAdditionalInfo();

        $tdsAmount = array_pull($additionalInfo, PayoutDetailsEntity::TDS_AMOUNT_KEY, 0);

        $data = [
            'entity_id'      => $payout->getPublicId(),
            'entity_type'    => Constants::ENTITY_TYPE,
            'entity_status'  => $payout->getStatus(),
            'merchant_id'    => $payout->getMerchantId(),
            'tds_category_id'=> $tdsCategoryId,
            'tds_amount'     => $tdsAmount,
        ];

        $metroMessage = [
            'data' => json_encode($data, true),
            'attributes' => [
                'mode' => $this->app['rzp.mode'] ?? Mode::LIVE,
            ]
        ];

        $this->trace->info(TraceCode::PROCESSING_TDS_FOR_PAYOUT, $data);

        try
        {
            $response = $this->app['metro']->publish(Constants::TDS_PROCESSOR_METRO_TOPIC, $metroMessage);

            $this->trace->info(TraceCode::TDS_FOR_PAYOUT_METRO_MESSAGE_PUBLISHED,
                [
                    'topic'    => Constants::TDS_PROCESSOR_METRO_TOPIC,
                    'response' => $response,
                ]);

        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::TDS_FOR_PAYOUT_METRO_MESSAGE_PUBLISH_ERROR,
                $data);

            throw $e;
        }
    }
}
