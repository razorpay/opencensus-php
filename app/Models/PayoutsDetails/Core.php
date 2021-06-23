<?php

namespace RZP\Models\PayoutsDetails;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(bool $queueIfLowBalance, Payout\Entity $payout)
    {
        $payoutId = $payout->getId();

        $input = [
            Entity::PAYOUT_ID                 => $payoutId,
            Entity::QUEUE_IF_LOW_BALANCE_FLAG => $queueIfLowBalance,

        ];

        $this->trace->info(
            TraceCode::PAYOUT_DETAILS_ENTITY_CREATE_REQUEST,
            $input
        );

        $payoutDetails = (new Entity)->build($input);

        $this->repo->saveOrFail($payoutDetails);

        $this->trace->info(
            TraceCode::PAYOUT_DETAILS_ENTITY_CREATED,
            $payoutDetails->toArray()
        );
    }


}
