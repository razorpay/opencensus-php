<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Processor\DownstreamProcessor\Base as DSBase;

class Base extends DSBase
{
    protected function setChannel(Entity $payout)
    {
        //
        // NOTE (for queued only): When the payout is being queued,
        // the payout might have channel A set. Once we start processing,
        // it's possible that we are selecting channel B (based on what
        // DR module returns at that point of time).
        // Hence, irrespective of the channel set at the time of queued,
        // the channel that will be actually used is of when the queued
        // payout is being processed.
        //

        $channel = snake_case(class_basename(get_called_class()));

        $payout->setChannel($channel);
    }
}
