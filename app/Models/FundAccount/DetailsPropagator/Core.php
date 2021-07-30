<?php

namespace RZP\Models\FundAccount\DetailsPropagator;

use App;
use RZP\Trace\TraceCode;
use \RZP\Constants\Mode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\SlackNotification;
use RZP\Jobs\FundAccountDetailsPropagatorJob;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;

/**
 * Class DetailsPropagator
 *
 * This class propagates the fund account details to the subscribers
 *
 * @package RZP\Models\FundAccount\DetailsPropagator
 */

class Core
{
    const SLACK_ALERTS_CHANNEL = 'x-alerts';

    const FUND_ACCOUNT_ID = 'fund_account_id';

    public static function dispatchToQueue(string $mode,
                                           string $fundAccountId)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->info(TraceCode::FUND_ACCOUNT_DETAILS_PROPAGATOR_QUEUE_PUSH,
            [
                self::FUND_ACCOUNT_ID => $fundAccountId
            ]);

        try
        {
            FundAccountDetailsPropagatorJob::dispatch($mode,
                $fundAccountId);
        }
        catch (\Exception $e)
        {

            $trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_TO_PUSH_TO_FA_DETAILS_PROPAGATOR_JOB_QUEUE
            );

            $alertData = [
                self::FUND_ACCOUNT_ID => $fundAccountId
            ];

            (new SlackNotification)->send(
                '[RX]-[APPS]-[Vendor Payments]-[Failed to enqueue fund account details update for app]',
                $alertData,
                $e,
                1,
                self::SLACK_ALERTS_CHANNEL);
        }
    }

    /**
     * @param FundAccountEntity $fundAccount
     * @param string $mode
     */
    public static function update(FundAccountEntity $fundAccount, string $mode = Mode::LIVE)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $trace->info(TraceCode::FUND_ACCOUNT_DETAILS_PROPAGATOR_JOB_PROCESSING,
            [
                self::FUND_ACCOUNT_ID => $fundAccount->getPublicId(),
                MerchantEntity::MERCHANT_ID => $fundAccount->getMerchantId()
            ]);

        $subscriberList = Factory::getSubscribers();

        foreach ($subscriberList as $subscriber)
        {
            $subscriber->update($fundAccount, $mode);
        }
    }
}
