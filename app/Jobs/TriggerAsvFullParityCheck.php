<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class TriggerAsvFullParityCheck extends Job
{
    protected $maxCount = 10000;

    protected $maxBatchSize = 1000;

    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    public $timeout = 1800;

    protected $input = array();

    public function __construct(string $mode, array $input)
    {
        $this->input = $input;
        parent::__construct($mode);
    }

    public function handle()
    {
        parent::handle();

        RuntimeManager::setMemoryLimit('2048M');

        $this->input[Constant::COUNT] = min(($this->input[Constant::COUNT] ?? $this->maxCount), $this->maxCount);
        $this->input[Constant::AFTER_MERCHANT_ID] = $this->input[Constant::AFTER_MERCHANT_ID] ?? null;
        $this->input[Constant::PARITY_CHECK_ENTITY] = $this->input[Constant::PARITY_CHECK_ENTITY] ?? Constant::ALL_ENTITY;
        $this->input[Constant::PARITY_CHECK_METHODS] = $this->input[Constant::PARITY_CHECK_METHODS] ?? [Constant::GET_BY_MERCHANT_ID];
        $this->input[Constant::PARITY_CHECK_PERCENTAGE] = $this->input[Constant::PARITY_CHECK_PERCENTAGE] ?? 100;
        $batchSize = min(($this->input[Constant::BATCH_SIZE] ?? $this->maxBatchSize), $this->maxBatchSize);

        try {
            $merchantIds = match ($this->input[Constant::PARITY_CHECK_ENTITY]) {
                Constant::MERCHANT => $this->getMerchantIds(
                    $this->repoManager->merchant->fetchAllMerchantIDsFromSlaveDB([
                        Constant::COUNT => $this->input[Constant::COUNT],
                        Constant::AFTER_ID => $this->input[Constant::AFTER_MERCHANT_ID]
                    ])->toArray(),
                    key: Constant::ID),
                Constant::MERCHANT_WEBSITE => $this->getMerchantIds(
                    $this->repoManager->merchant_website->fetchAllMerchantIDsFromSlaveDB([
                        Constant::COUNT => $this->input[Constant::COUNT],
                        Constant::AFTER_MERCHANT_ID => $this->input[Constant::AFTER_MERCHANT_ID]
                    ])->toArray(),
                    key: Constant::MERCHANT_ID),
                default => $this->getMerchantIds(
                    $this->repoManager->merchant->fetchAllMerchantIDsFromSlaveDB([
                        Constant::COUNT => $this->input[Constant::COUNT],
                        Constant::AFTER_ID => $this->input[Constant::AFTER_MERCHANT_ID]
                    ])->toArray(),
                    key: Constant::ID),
            };

            if (count($merchantIds) === 0) {
                $this->delete();
                return;
            }

            $this->trace->info(TraceCode::ASV_TRIGGER_PARITY_CHECK,
                [Constant::INPUT => $this->input, Constant::LAST_ID => $merchantIds[count($merchantIds) - 1]]);

            $merchantIds = $this->selectSubsetMerchantIds($merchantIds, $this->input[Constant::PARITY_CHECK_PERCENTAGE]);

            // calling in batches of size given in input
            $batches = array_chunk($merchantIds, $batchSize);

            foreach ($batches as $batch) {
                TriggerAsvParityCheck::dispatch($this->mode, $batch, $this->input[Constant::PARITY_CHECK_ENTITY], $this->input[Constant::PARITY_CHECK_METHODS]);
            }

            $this->delete();
        } catch (\Throwable $e) {
            $this->countJobException($e);

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ASV_TRIGGER_PARITY_CHECK_ERROR,
                [
                    Constant::MODE => $this->mode,
                ]
            );
        }
    }

    function getMerchantIds(array $entityArray, string $key): array
    {
        $merchantIds = [];
        foreach ($entityArray as $entity) {
            $mid = $entity[$key] ?? '';
            $merchantIds[] = $mid;
        }

        return $merchantIds;
    }

    function selectSubsetMerchantIds(array $merchantIds, int $percentage): array
    {
        $subsetMerchantIds = [];
        foreach ($merchantIds as $merchantId) {
            $randomInt = rand(1, 100);
            if ($randomInt > $percentage) {
                continue;
            }
            $subsetMerchantIds[] = $merchantId;
        }

        return $subsetMerchantIds;
    }
}
