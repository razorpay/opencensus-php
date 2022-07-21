<?php

namespace RZP\Jobs\Order;

use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Models\Order\Entity;
use RZP\Trace\TraceCode;


class OrderUpdate extends Job
{
    const RETRY_INTERVAL    = 300;
    const MAX_RETRY_ATTEMPT = 3;

    protected $input;

    protected $order;


    public function __construct(string $mode, array $input, Entity $order)
    {
        parent::__construct($mode);

        $this->input = $input;

        $this->order = $order;
    }

    public function handle()
    {
        parent::handle();

        if ((app()->isEnvironmentProduction() === true) and
            ($this->mode === Mode::TEST))
        {
            return;
        }

        try
        {
            app('pg_router')->updateInternalOrder($this->input,$this->order->getId(),$this->order->getMerchantId(), true);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::PG_ROUTER_ORDER_UPDATE_FAILED,
                [

                ]);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::PG_ROUTER_ORDER_QUEUE_DELETE, [
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

