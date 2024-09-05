<?php


namespace RZP\Listeners;

use RZP\Constants\Metric;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Key;
use RZP\Trace\TraceCode;
use Throwable;

class KeyEventListener
{
    public function onRetrieved(Key\EventRetrieved $event)
    {
        if ((app()->runningUnitTests() === true) or (app()->runningInQueue() === true)) {
            return;
        }

        try {
            // to handle cardinality ??
            $rand = rand(1, 500000);

            if ($rand > 250000) {
                return;
            }

            $entity = $event->entity;
            (new Key\Service())->logRouteName($entity->getId(), "read", TraceCode::KEYS_RETRIEVAL_EVENT, Metric::KEY_READ_REQUEST);


        } catch (Throwable $e) {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::KEY_RETRIEVAL_EXCEPTION, []);
        }
    }

    public function onSaved(Key\EventSaved $event)
    {
        if ((app()->runningUnitTests() === true) or (app()->runningInQueue() === true)) {
            return;
        }

        // not adding sampling here cause of low frequency of key write operations
        try {
            $entity = $event->entity;

            (new Key\Service())->logRouteName($entity->getId(), "write", TraceCode::KEYS_SAVED_EVENT, Metric::KEY_WRITE_REQUEST);

        } catch (Throwable $e) {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::KEY_SAVED_EXCEPTION, []);
        }

    }

}
