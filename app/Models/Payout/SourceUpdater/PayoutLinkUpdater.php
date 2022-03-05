<?php

namespace RZP\Models\Payout\SourceUpdater;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\PayoutLink\Core as PayoutLinkCore;

class PayoutLinkUpdater extends Base
{
    public function update()
    {
        try
        {
            if (self::checkIfPLServiceIsDown($this->payout) == true)
            {
                // Payout Link Service is DOWN
                return;
            }

            $payoutLinkId = $this->payout->getPayoutLinkId();

            if($payoutLinkId !== null)
            {
                $trace = $this->app['trace'];

                // check if payout link microservice feature flag enabled for this merchant
                if(self::checkIfMerchantOnAPI($this->payout) == true)
                {
                    $trace->info(
                        TraceCode::PAYOUT_LINKS_API_ROUTE,
                        [
                            'payout_link_id'   => $payoutLinkId,
                        ]);

                    $payoutLink = $this->payout->payoutLink;

                    if($payoutLink !== null)
                    {
                        (new PayoutLinkCore())->payoutUpdateListener($payoutLink, $this->payout);
                    }
                }
                else
                {
                    $trace->info(
                        TraceCode::PAYOUT_LINKS_MS_ROUTE,
                        [
                            'payout_link_id'   => $payoutLinkId,
                        ]);

                    $payoutLinkService = $this->app['payout-links'];

                    $payoutLinkService->pushPayoutStatus($payoutLinkId, $this->payout->getId(), $this->payout->getStatus(), $this->mode);
                }
            }
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::PAYOUT_LINK_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $this->payout->getPublicId(),
                                   ]);
        }
    }

    protected static function checkIfMerchantOnAPI(PayoutEntity $payout) : bool
    {
        // todo temp fix https://jira.corp.razorpay.com/browse/RX-3668
        return false;

        $app = App::getFacadeRoot();

        $mid = $payout->merchant->getId();

        $variant = $app['razorx']->getTreatment($mid,
                                                Merchant\RazorxTreatment::RX_PAYOUT_LINK_MICROSERVICE,
                                                $app['rzp.mode'] ?? 'live');

        return !($variant == 'on');
    }

    protected static function checkIfPLServiceIsDown(PayoutEntity $payout) : bool
    {
        // todo temp fix https://jira.corp.razorpay.com/browse/RX-3668
        return false;

        $app = App::getFacadeRoot();

        $mid = $payout->merchant->getId();

        $variant = $app['razorx']->getTreatment($mid,
                                                Merchant\RazorxTreatment::RX_IS_PAYOUT_LINK_SERVICE_DOWN,
                                                $app['rzp.mode'] ?? 'live');

        return $variant == 'on';
    }

}
