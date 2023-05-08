<?php

namespace RZP\Models\FundTransfer\Attempt;

final class Metric
{
    // ------------------------- Metrics -------------------------

    // ------ Counters ------

    /**
     * Method: Count
     * Dimensions: Channel, Purpose, SourceType
     *
     * Number of times an attempt was initiated successfully
     */
    const ATTEMPTS_INITIATE_SUCCESS_TOTAL   = 'attempts_initiate_success_total';

    /**
     * Method: Count
     * Dimensions: Channel, Purpose, SourceType
     *
     * Number of times that an attempt could not be initiated
     */
    const ATTEMPTS_INITIATE_FAILURE_TOTAL   = 'attempts_initiate_failure_total';

    /**
     * Method: Count
     * Dimensions: Channel, BankStatusCode, SourceType, FailureBucket
     *
     * Number of times an initiated attempt failed
     */
    const ATTEMPTS_FAILED_TOTAL             = 'attempts_failed_total';

    /**
     * Method: Count
     * Dimensions: Sla
     *
     * Number of times initiated attempts are sent for transfer after sla expiry
     */
    const FTA_SLA_EXPIRED                   = 'fta_sla_expired';

    // ------ Histograms ------

    /**
     * Method: Histogram
     * Dimensions: Channel, SourceType
     *
     * Time taken to receive an UTR for an attempt since it was initiated
     */
    const ATTEMPTS_TIME_FOR_UTR_MINUTES     = 'attempts_time_for_utr_minutes.histogram';

    // ------------------------- Dimensions -------------------------

    const SLA               = 'sla';
    const CHANNEL           = 'channel';
    const PURPOSE           = 'purpose';
    const SOURCE_TYPE       = 'source_type';
    const BANK_STATUS_CODE  = 'bank_status_code';

    // ------ Dimension values for failures ------

    const FAILURE_BUCKET    = 'failure_bucket';
    const MERCHANT_ERROR    = 'merchant_error';
    const RZP_ERROR         = 'rzp_error';
    const NODAL_BANK_ERROR  = 'nodal_bank_error';

    const WEBHOOK_UPDATE_FAILURE_COUNT = 'webhook_update_failure_count';

    public static function getDimensionsAttemptsInitiated($channel, $purpose = null, $sourceType): array
    {
        return [
            self::CHANNEL     => $channel,
            self::PURPOSE     => $purpose,
            self::SOURCE_TYPE => $sourceType
        ];
    }
}
