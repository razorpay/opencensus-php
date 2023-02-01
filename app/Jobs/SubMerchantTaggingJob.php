<?php

namespace RZP\Jobs;

use Throwable;
use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Metric;
use Jitendra\Lqext\TransactionAware;

class SubMerchantTaggingJob extends Job
{
    use TransactionAware;

    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $partnerId;

    protected $subMerchantId;

    protected $tagPrefix;

    public function __construct($mode, string $partnerId, string $subMerchantId, string $tagPrefix)
    {
        parent::__construct($mode);
        $this->subMerchantId = $subMerchantId;
        $this->partnerId     = $partnerId;
        $this->tagPrefix     = $tagPrefix;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::SUBMERCHANT_TAGGING_ASYNC_JOB,
            [
                'partner_id'  => $this->partnerId,
                'merchant_id' => $this->subMerchantId,
                'tag_prefix'  => $this->tagPrefix,
            ]
        );

        try
        {
            $partner     = $this->repoManager->merchant->findOrFailPublic($this->partnerId);
            $subMerchant = $this->repoManager->merchant->findOrFailPublic($this->subMerchantId);

            $existingTags = $subMerchant->tagNames();

            $refTag = $this->tagPrefix . $partner->getId();

            if (in_array(strtolower($refTag), array_map('strtolower', $existingTags)) === true)
            {
                return;
            }

            (new Merchant\Core())->appendTag($subMerchant, $refTag);

            $this->delete();
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::SUBMERCHANT_TAGGING_ASYNC_JOB_FAILED,
                [
                    'partner_id'  => $this->partnerId,
                    'merchant_id' => $this->subMerchantId,
                    'tag_prefix'  => $this->tagPrefix
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function checkRetry(Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(
                TraceCode::SUBMERCHANT_TAGGING_ASYNC_JOB_MESSAGE_DELETE,
                [
                    'partner_id'   => $this->partnerId,
                    'merchant_id'  => $this->subMerchantId,
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->trace->count(Metric::SUBMERCHANT_TAGGING_FAILURE_TOTAL, []);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
