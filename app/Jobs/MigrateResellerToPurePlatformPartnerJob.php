<?php


namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Partner\MigrateResellerToPurePlatformPartner;

class MigrateResellerToPurePlatformPartnerJob extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 2;

    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $merchantId;
    protected $actorDetails;

    /**
     * Create a new job instance.
     * @param $merchantId    string      Partner's merchant_id
     *
     * @return void
     */
    public function __construct(string $merchantId, array $actorDetails)
    {
        parent::__construct();

        $this->merchantId = $merchantId;

        $this->actorDetails = $actorDetails;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::MIGRATE_RESELLER_TO_PURE_PLATFORM_JOB_REQUEST,
            ['merchant_id' => $this->merchantId]
        );

        try
        {
            (new MigrateResellerToPurePlatformPartner())->migrate($this->merchantId, $this->actorDetails);

            $this->delete();
        }
        catch (\Throwable $exception)
        {
            if (
                ($exception instanceof BadRequestException) === true &&
                $exception->getCode() == ErrorCode::BAD_REQUEST_RESELLER_TO_PURE_PLATFORM_MIGRATION_IN_PROGRESS
            )
            {
                $this->delete();
            }
            else
            {
                $this->trace->traceException(
                    $exception, Trace::ERROR,
                    TraceCode::MIGRATE_RESELLER_TO_PURE_PLATFORM_JOB_ERROR,
                    [ 'mode' => $this->mode ]
                );

                $this->checkRetry($exception);
            }
        }
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::MIGRATE_RESELLER_TO_PURE_PLATFORM_QUEUE_DELETE, [
                'merchant_id'  => $this->merchantId,
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
