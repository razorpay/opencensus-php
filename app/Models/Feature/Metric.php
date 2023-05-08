<?php

namespace RZP\Models\Feature;

final class Metric
{
    const FEATURE_ASSIGN_TOTAL = 'feature_assign_total';
    const FEATURE_ASSIGN_FAILURE_TOTAL = 'feature_assign_failure_total';
    const FEATURE_REMOVE_TOTAL = 'feature_remove_total';
    const FEATURE_REMOVE_FAILURE_TOTAL = 'feature_remove_failure_total';
    const DCS_FEATURE_FETCH_TOTAL = 'dcs_feature_fetch_total';
    const DCS_FEATURE_FETCH_FAILURE_TOTAL = 'dcs_feature_fetch_failure_total';

    const MFN_WEBHOOK_CREATE_FAILURE = 'mfn_webhook_create_failure';
    const LEDGER_FEATURE_REMOVAL_COUNT = 'ledger_feature_removal_count';
}
