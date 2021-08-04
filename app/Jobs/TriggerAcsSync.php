<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Acs;
use RZP\Trace\TraceCode;

class TriggerAcsSync extends Job
{
    protected $queueConfigKey = 'commission';

    public $timeout = 1000;

    protected $merchantIds;

    public function __construct(string $mode, array $merchantIds)
    {
        parent::__construct($mode);

        $this->merchantIds = $merchantIds;
    }

    public function handle()
    {
        parent::handle();

        RuntimeManager::setMemoryLimit('2048M');

        try
        {
            $input['account_ids'] = $this->merchantIds;
            $input['mode'] = $this->mode;

            (new Acs\Service)->triggerSync($input);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ACS_TRIGGER_SYNC_ERROR,
                [
                    'mode'          => $this->mode,
                    'merchant_ids'  => $this->merchantIds,
                ]
            );
        }
    }
}