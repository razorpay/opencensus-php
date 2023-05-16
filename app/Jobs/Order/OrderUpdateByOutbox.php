<?php

namespace RZP\Jobs\Order;

use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Constants\Metric;
use RZP\Trace\TraceCode;
use RZP\Models\OrderOutbox as OrderOutbox;

class OrderUpdateByOutbox extends Job
{
    protected $orderOutboxId;

    public function __construct(string $mode, string $orderOutboxId)
    {
        parent::__construct($mode);

        $this->orderOutboxId = $orderOutboxId;
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
            $orderOutboxCore = New OrderOutbox\Core;

            $orderOutbox = (new OrderOutbox\Repository)->findOrFail($this->orderOutboxId);

            $this->trace->info(TraceCode::ORDER_OUTBOX_FETCH,
                [
                    OrderOutbox\Constants::ORDER_OUTBOX         => $orderOutbox
                ]
            );

            $this->mutex->acquireAndRelease(
                $orderOutbox->getOrderId() . OrderOutbox\Constants::ORDER_UPDATE_MUTEX,
                function () use ($orderOutbox, $orderOutboxCore)
                {
                    $payload = json_decode($orderOutbox->getPayload(), true);

                    $response = app('pg_router')->updateInternalOrder($payload,
                        $orderOutbox->getOrderId(), $orderOutbox->getMerchantId(), true);

                    if ($response !== null)
                    {
                        $orderOutboxCore->softDelete($orderOutbox);
                    }

                }
            );
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::PG_ROUTER_ORDER_UPDATE_FAILED,
                [
                    'message'           => 'order update failed',
                    'order_outbox__id'  => $this->orderOutboxId,
                ]);

            $this->trace->count(Metric::ORDER_OUTBOX_SYNC_UPDATE_FAILURE, [
                OrderOutbox\Entity::ID        => $this->orderOutboxId,
            ]);
        }
    }
}

