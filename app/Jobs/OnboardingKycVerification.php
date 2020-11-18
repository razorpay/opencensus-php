<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\Constants;

class OnboardingKycVerification extends Job
{
    const MAX_RETRY_ATTEMPT = 2;

    protected $queueConfigKey = 'onboarding_kyc_verification';

    protected $documentType;

    protected $merchantId;

    protected $kycServiceRetryDelayInSecond;

    public function __construct(string $mode, string $documentType, string $merchantId)
    {
        parent::__construct($mode);

        $this->documentType = $documentType;

        $this->merchantId = $merchantId;

        $app = App::getFacadeRoot();

        $this->kycServiceRetryDelayInSecond = (int) $app['config']['applications.kyc']['retry_delay'];
    }

    public function handle()
    {
        parent::handle();

        // Trace payload should include all necessary info for debugging.
        try
        {
            $tracePayload = [
                'job_attempts'  => $this->attempts(),
                'mode'          => $this->mode,
                'document_type' => $this->documentType,
                'merchant_id'   => $this->merchantId,
            ];

            $this->trace->debug(TraceCode::ONBOARDING_KYC_VERIFICATION_JOB_REQUEST, $tracePayload);

            $this->retryVerification();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ONBOARDING_KYC_VERIFICATION_JOB_ERROR,
                [
                    'job_attempts'  => $this->attempts(),
                    'mode'          => $this->mode,
                    'document_type' => $this->documentType,
                    'merchant_id'   => $this->merchantId,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::ONBOARDING_KYC_VERIFICATION_JOB_DELETE, [
                'document_type' => $this->documentType,
                'merchant_id'   => $this->merchantId,
                'job_attempts'  => $this->attempts(),
                'message'       => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release($this->kycServiceRetryDelayInSecond);
        }
    }

    public function retryVerification()
    {
    }
}
