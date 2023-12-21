<?php

namespace RZP\Jobs;

use Throwable;
use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Metric;
use Jitendra\Lqext\TransactionAware;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\AccessMap\Service as AccessMapService;
use RZP\Models\Merchant\MerchantApplications\Repository as MerchantAppRepo;

class PartnerSubmerchantLinkingOauthJob extends Job
{
    use TransactionAware;

    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected        $queueConfigKey = 'commission';

    protected        $metricsEnabled = true;

    protected string $subMerchantId;

    protected string  $sourceAppId;

    public function __construct($mode, string $subMerchantId, string $sourceAppId)
    {
        parent::__construct($mode);
        $this->subMerchantId = $subMerchantId;
        $this->sourceAppId = $sourceAppId;

    }

    public function getSourceAppId(): string
    {
        return $this->sourceAppId;
    }

    public function getMerchantId(): string
    {
        return $this->subMerchantId;
    }

    public function handle(): void
    {
        parent::handle();

        $this->trace->info(
            TraceCode::OAUTH_MERCHANT_ACCESS_MAP_ASYNC_JOB,
            [
                "submerchant_id" => $this->subMerchantId,
                "source_app_id" => $this->sourceAppId,
            ]
        );

        try
        {

            $merchantApp = (new MerchantAppRepo)->fetchMerchantApplication(
                $this->sourceAppId,
                MerchantConstants::APPLICATION_ID,
            );

            $input = [
                MerchantConstants::PARTNER_ID     => $merchantApp[0][MerchantConstants::MERCHANT_ID],
                MerchantConstants::APPLICATION_ID => $this->sourceAppId,
            ];

            $this->repoManager->transactionOnLiveAndTest(function() use ($input) {
                (new AccessMapService())->mapOAuthApplication($this->subMerchantId, $input);
            });

            $this->delete();
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::OAUTH_MERCHANT_ACCESS_MAP_ASYNC_JOB_FAILED,
                [
                    "submerchant_id" => $this->subMerchantId,
                    "source_app_id"  => $this->sourceAppId,
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function checkRetry(Throwable $e): void
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(
                TraceCode::OAUTH_MERCHANT_ACCESS_MAP_ASYNC_JOB_MESSAGE_DELETE,
                [
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->trace->count(Metric::SUBM_SIGNUP_LINKING_PP_REFERRAL_FAILURE_TOTAL);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
