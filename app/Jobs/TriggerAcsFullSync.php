<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;
use RZP\Base\RuntimeManager;
use RZP\Trace\TraceCode;

class TriggerAcsFullSync extends Job
{
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

        $this->input['count'] = $this->input['count'] ?? 2000;
        $this->input['afterId'] = $this->input['afterId'] ?? null;
        $this->input['operation'] = $this->input['operation'] ?? 'full_sync';
        $batchSize = $this->input['batch_size'] ?? 1000;

        try {
            $ids = $this->repoManager->merchant->fetchAllMerchantIDsFromSlaveDB(['count' => $this->input['count'], 'afterId' => $this->input['afterId']]);

            if ($ids->isEmpty() === true) {
                $this->delete();
                return;
            }

            $this->trace->info(TraceCode::ACS_TRIGGER_SYNC, ['input' => $this->input, 'lastId' => $ids->last()]);

            // calling in batches of size given in input
            $batches = array_chunk($ids->getIds(), $batchSize);

            foreach ($batches as $batch) {
                TriggerAcsSync::dispatch($this->mode, $batch, $this->input['outbox_jobs'], $this->input['operation']);
            }

            $this->delete();
        } catch (\Throwable $e) {
            $this->countJobException($e);

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ACS_TRIGGER_SYNC_ERROR,
                [
                    'mode' => $this->mode,
                ]
            );
        }
    }
}
