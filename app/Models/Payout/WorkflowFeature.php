<?php

namespace RZP\Models\Payout;

use RZP\Models\Feature\Constants;

class WorkflowFeature
{
    const SKIP_FOR_PG_PAYOUT        = 'skip_for_pg_payout';
    const SKIP_FOR_INTERNAL_PAYOUT  = 'skip_for_internal_payout';
    const SKIP_WF_FOR_XPERIENCE     = 'skip_wf_for_xperience';

    const WORKFLOW_FEATURES = [
        Constants::PAYOUT_WORKFLOWS        => 1,
        Constants::SKIP_WF_AT_PAYOUTS      => 2,
        Constants::SKIP_WORKFLOWS_FOR_API  => 3,
        self::SKIP_FOR_INTERNAL_PAYOUT     => 4,
        self::SKIP_FOR_PG_PAYOUT           => 5,
        Constants::SKIP_WF_FOR_PAYROLL     => 6,
        Constants::SKIP_WF_FOR_PAYOUT_LINK => 7,
        Constants::BULK_PAYOUT_WORKFLOW    => 8,
        self::SKIP_WF_FOR_XPERIENCE        => 9,
    ];

    public static function getIntValueFromWorkflowFeature($feature)
    {
        return self::WORKFLOW_FEATURES[$feature];
    }

    public static function getWorkflowFeatureFromInt($featureInt)
    {
        return array_search($featureInt,self::WORKFLOW_FEATURES);
    }
}
