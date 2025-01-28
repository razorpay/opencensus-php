<?php

namespace RZP\Jobs\CrossBorder;

use Razorpay\Trace\Logger;

use RZP\Models\Base\Core;
use RZP\Trace\TraceCode;

class Metrics extends Core
{
    // Counters
    const CROSS_BORDER_COMMON_WORKER_JOB_FAILED = 'cross_border_common_worker_job_failed';

    const INVOICE_ZIP_FILE_NOT_UPLOADED_PROPERLY = 'invoice_zip_file_not_uploaded_properly';

    const CROSS_BORDER_MERCHANT_ACTIVATION_FAILED = 'cross_border_merchant_activation_failed';

    // Dimensions
    const ACTION = 'action';
    const IS_DELETED = 'is_deleted';

    public function pushErrorMetrics($labelName, $dimensions)
    {
        try
        {
            $this->trace->count($labelName, $dimensions);

            $this->trace->info(
                TraceCode::CROSS_BORDER_METRICS_PUSH_SUCCESS,
                $dimensions,
            );
        }
        catch(\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Logger::ERROR,
                TraceCode::CROSS_BORDER_METRICS_PUSH_FAILED,
                $dimensions,
            );
        }
    }

}
