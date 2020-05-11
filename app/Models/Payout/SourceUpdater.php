<?php

namespace RZP\Models\Payout;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\PayoutSourceUpdaterJob;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\PayoutLink\Core as PayoutLinkCore;
use RZP\Models\PayoutLink\Entity as PayoutLinkEntity;

/**
 * Class SourceUpdater
 *
 * This class will check the source that created the payout and push status updates to it.
 *
 * @package RZP\Models\Payout
 */

class SourceUpdater
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

        PayoutSourceUpdaterJob::dispatch($mode,
                                         $payout->getPublicId(),
                                         $previousStatus,
                                         $expectedCurrentStatus)
                              ->delay(self::DELAY);
    }

    /**
     * Currently there is only one source, which is the payout link app,
     * and so this will be a direct function call to its core.
     * Later there will be multiple sources, and only this code will need changes.
     * Ex: calling a webhook URL based on the source
     * @param Entity $payout
     * @param string $previousPayoutStatus
     */
    public static function update(Entity $payout, string $previousPayoutStatus = null)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->info(TraceCode::PAYOUT_SOURCE_UPDATER_PROCESSING,
            [
                'payout_id'              => $payout->getPublicId(),
                'previous_payout_status' => $previousPayoutStatus
            ]);

        if ($payout->getStatus() !== $previousPayoutStatus)
        {
            self::updatePayoutLink($payout);

            self::updateVendorPayment($payout);
        }
    }

    protected static function updatePayoutLink(PayoutEntity $payout)
    {
        try
        {
            if (($payout->payoutLink !== null))
            {
                (new PayoutLinkCore())->payoutUpdateListener($payout->payoutLink, $payout);
            }
        }
        catch (\Exception $e)
        {
            $trace = App::getFacadeRoot()['trace'];

            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::PAYOUT_LINK_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $payout->getPublicId(),
                                   ]);
        }
    }

    protected static function updateVendorPayment(PayoutEntity $payout)
    {
        try
        {
            $vendorPaymentService = App::getFacadeRoot()['vendor-payment'];

            $vendorPaymentService->pushPayoutStatusUpdate($payout);
        }
        catch (\Exception $e)
        {
            $trace = App::getFacadeRoot()['trace'];

            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::VENDOR_PAYMENT_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $payout->getPublicId(),
                                   ]);
        }
    }
}
