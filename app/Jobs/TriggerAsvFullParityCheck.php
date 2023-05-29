<?php

namespace RZP\Jobs;

use RZP\Models\Merchant\Acs\ParityChecker\Helper\FetchMerchant;
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
            $merchantFetch = new FetchMerchant();
            $merchantIds = $merchantFetch->fetchAllMerchantIdsFromSlaveDB(
                $this->input[Constant::PARITY_CHECK_ENTITY],
                $this->input[Constant::COUNT],
                $this->input[Constant::AFTER_MERCHANT_ID]
            );

            if (count($merchantIds) === 0) {
                $this->delete();
                return;
            }

            $this->trace->info(TraceCode::ASV_TRIGGER_PARITY_CHECK,
                [Constant::INPUT => $this->input, Constant::LAST_ID => $merchantIds[count($merchantIds) - 1]]);

            $merchantIds = $merchantFetch->selectSubsetMerchantIds($merchantIds, $this->input[Constant::PARITY_CHECK_PERCENTAGE]);

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
}
