<?php

namespace RZP\Models\FeeRecovery;

final class Metric
{
    //Metric_Names
    const FEE_RECOVERY_ISSUE = "FEE_RECOVERY_ISSUE";
    const FEE_RECOVERY_DATA_CORRECTION_JOB  = "FEE_RECOVERY_DATA_CORRECTION_JOB";

    //Labels
    const CODE = "code";
    const STATUS = "status";
    const SUCCESS = "success";
    const FAILURE = "failure";
    const NO_ISSUE = "no_issue";
    const RESOLVED = "resolved";
    const INITIATED = "initiated";
    const UNRESOLVED = "unresolved";
    const NEGATIVE_COUNT = "negative_count";
}
