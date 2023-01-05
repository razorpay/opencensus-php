<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Activation;

class PartnerActivationMigration extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    public $timeout = 1800;

    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $merchantIds;

    public function __construct(string $mode, $merchantIds)
    {
        parent::__construct($mode);

        $this->merchantIds = $merchantIds;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::PARTNER_ACTIVATION_MIGRATION_DETAILS, ['mids' => $this->merchantIds]);

        try {
            $core = new Activation\Core();

            foreach ($this->merchantIds as $merchantId)
            {
                $merchant = $this->repoManager->merchant->findOrFailPublic($merchantId);
                $core->createOrFetchPartnerActivationForMerchant($merchant, false);
            }

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PARTNER_ACTIVATION_MIGRATION_FAILED,
                [
                    'mode' => $this->mode,
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::PARTNER_ACTIVATION_MIGRATION_JOB_DELETE, [
                'id'           => $this->merchantIds,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
