<?php

namespace RZP\Models\Payout\SourceUpdater;

use App;
use RZP\Trace\TraceCode;
use \RZP\Constants\Mode;

use RZP\Models\Payout\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\PayoutSourceUpdaterJob;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Payout\Entity as PayoutEntity;


/**
 * Class SourceUpdater
 *
 * This class will check the source that created the payout and push status updates to it.
 *
 * @package RZP\Models\Payout\SourceUpdater
 */

class Core
{
    /**
     * Value in Seconds.
     * We need to delay dispatching payout status update to its source, so that we can ensure
     * that the transaction is completed.
     * In case the transaction fails, then the current and previous status of payout will be the same,
     * and the SourceUpdater will reject the push to source
     */
    const DELAY = 5;

    public static function dispatchToQueue(string $mode,
                                           PayoutEntity $payout,
                                           string $previousStatus = null,
                                           string $expectedCurrentStatus = null)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->info(TraceCode::PAYOUT_SOURCE_UPDATER_QUEUE_PUSH,
                     [
                         'payout_id'               => $payout->getPublicId(),
                         'previous_status'         => $previousStatus,
                         'expected_current_status' => $expectedCurrentStatus
                     ]);

        // we push it to queue, only if it has payout-sources that need this status update
        $sourceDetails = $payout->getSourceDetails();

        $pushStatusUpdate = false;

        if ($sourceDetails->count() !== 0)
        {
            $pushStatusUpdate = true;
        }
        else
        {
            if ($payout->getStatus() === Status::CREATED)
            {
                // While payout is being created, because of the inserts being inside a transaction, it is possible
                // that source details are empty, hence pushing the event to queue, where it will be filtered out.
                // TODO: Fix this not to push updates in the first place.
                $pushStatusUpdate = true;
            }
            else if ((in_array($expectedCurrentStatus, [Status::PROCESSED, Status::REVERSED]) === true) and
                     (GenericAccountingUpdater::isGAIExperimentEnabled($payout->getMerchantId()) === true))
            {
                /*
                 * This is the case of Vanilla Payouts
                 * These status updates are required, for Generic Accounting Integrations,
                 * if the following are conditions are met,
                 *   1. If there are no SourceDetails
                 *   2. If the status is either Processed OR Reversed
                 *   3. If the GAI Experiment for this merchant is enabled
                 */
                $pushStatusUpdate = true;
            }
        }

        if ($pushStatusUpdate === false)
        {
            return;
        }

        try
        {
            PayoutSourceUpdaterJob::dispatch($mode,
                                             $payout->getPublicId(),
                                             $previousStatus,
                                             $expectedCurrentStatus)
                                  ->delay(self::DELAY);
        }
        catch (\Exception $e)
        {

            $trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_TO_PUSH_PAYOUT_SOURCE_UPDATER_QUEUE
            );

            $alertData = [
                'payout_id'               => $payout->getPublicId(),
                'previous_status'         => $previousStatus,
                'expected_current_status' => $expectedCurrentStatus
            ];

             (new SlackNotification)->send(
                    'Failed to enqueue payout status update for app',
                    $alertData,
                    $e,
                    1,
                    'x-payouts-core-alerts');
        }
    }

    /**
     * Currently there is only one source, which is the payout link app,
     * and so this will be a direct function call to its core.
     * Later there will be multiple sources, and only this code will need changes.
     * Ex: calling a webhook URL based on the source
     * @param PayoutEntity $payout
     * @param string $previousPayoutStatus
     * @param string $mode
     */
    public static function update(PayoutEntity $payout, string $previousPayoutStatus = null, string $mode = Mode::LIVE)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $trace->info(TraceCode::PAYOUT_SOURCE_UPDATER_PROCESSING,
                     [
                         'payout_id'               => $payout->getPublicId(),
                         'previous_payout_status'  => $previousPayoutStatus,
                         'current_status'          => $payout->getStatus()
                     ]);

        if ($payout->getStatus() !== $previousPayoutStatus)
        {
            $subscriberList = Factory::getUpdaters($payout, $mode);

            foreach ($subscriberList as $subscriber)
            {
                /**
                 * Note: Some of the updaters may throw error if they are not able to send the data.
                 * This may cause this loop to break. As there is a retry mechanism at RZP\Jobs\PayoutSourceUpdaterJob::handle, the current function
                 * will be retried. This may cause resending the same status update to few source updaters.
                 * Eg. If XPayroll service is throwing error here via RZP\Models\Payout\SourceUpdater\XPayrollUpdater,
                 * it will stop propagation after XPayroll, and will send multiple status updates to subscribers prior to XPayroll.
                 *
                 * Some changes are required in this flow. Ideally retry should happen to only those subscribers who threw error.
                 *
                 * Slack thread: https://razorpay.slack.com/archives/CR3K6S6C8/p1621336941015300
                 */
                $subscriber->update();
            }
        }
    }
}
