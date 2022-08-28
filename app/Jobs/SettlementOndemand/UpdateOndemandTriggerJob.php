<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Constants\Mode;
use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;


class UpdateOndemandTriggerJob extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 10;

    protected $settlementOndemandId;

    /** @var Ondemand\Entity $settlementOndemand */
    protected $settlementOndemand;

    protected $event;

    protected $amount;


    public function __construct($settlementOndemandId, $event, $amount)
    {
        parent::__construct(Mode::LIVE);

        $this->settlementOndemandId = $settlementOndemandId;

        $this->event = $event;

        $this->amount = $amount;

    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->settlementOndemand = (new Ondemand\Repository)->findOrFail($this->settlementOndemandId);

            $this->trace->info(TraceCode::UPDATE_ONDEMAND_TRIGGER_JOB, [
                'settlement_ondemand_id'         => $this->settlementOndemandId,
                'settlement_ondemand_trigger_id' => $this->settlementOndemand->getSettlementOndemandTriggerId()
            ]);

            (new Ondemand\Service)->updateOndemandTrigger($this->settlementOndemand, $this->event, $this->amount);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPDATE_ONDEMAND_TRIGGER_JOB_FAILURE,
                [
                    'settlement_ondemand_id'         => $this->settlementOndemandId,
                ]);

            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(10 * $this->attempts() + random_int(0, 10));
            }

        }
    }
}
