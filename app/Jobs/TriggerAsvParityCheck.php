<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\ParityChecker\Service;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class TriggerAsvParityCheck extends Job
{
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    public $timeout = 1800;

    protected $merchantIds;

    protected $parityCheckEntity;

    protected $parityCheckMethods;


    public function __construct(string $mode, array $merchantIds, string $parityCheckEntity, array $parityCheckMethods)
    {
        parent::__construct($mode);

        $this->merchantIds = $merchantIds;

        $this->parityCheckEntity = $parityCheckEntity;

        $this->parityCheckMethods = $parityCheckMethods;
    }

    public function handle()
    {
        parent::handle();

        RuntimeManager::setMemoryLimit('2048M');

        try {
            $parityCheckService = new Service($this->merchantIds, $this->parityCheckEntity, $this->parityCheckMethods);
            $parityCheckService->triggerParityCheck();

            $this->delete();
        } catch (\Throwable $e) {
            $this->countJobException($e);

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ASV_TRIGGER_PARITY_CHECK_ERROR,
                [
                    Constant::MODE => $this->mode,
                    Constant::MERCHANT_IDS => $this->merchantIds,
                ]
            );
        }
    }
}
