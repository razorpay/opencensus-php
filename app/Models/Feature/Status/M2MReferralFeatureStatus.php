<?php

namespace RZP\Models\Feature\Status;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Store\Constants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Store\Core as StoreCore;
use GuzzleHttp\Promise\Tests\RejectionExceptionTest;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Feature\Entity as FeatureEntity;
use RZP\Models\Merchant\Store\Constants as StoreConstants;

class M2MReferralFeatureStatus extends BaseFeatureStatus
{
    protected $entity;

    /**
     * M2MReferralFeatureStatus constructor.
     *
     * @param $feature
     */
    public function __construct($feature)
    {
        parent::__construct($feature);
    }

    public function getFeatureStatus(): bool
    {
        try
        {
            //if experiment is enabled then only probe for gst details
            $isRazorxExperimentEnabled = (new Core())->isRazorxExperimentEnable(
                $this->feature->getEntityId(),
                RazorxTreatment::SHOW_FRIENDBUY_WIDGET);

            if ($isRazorxExperimentEnabled === false)
            {
                return false;
            }

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::GET_M2M_REFERRALS_FEATURE_STATUS_ERROR,
                                         [
                                             FeatureConstants::M2M_REFERRAL => $this->feature]);

            return false;
        }
        return true;

    }
}
