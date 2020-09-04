<?php

namespace RZP\Models\PayoutSource;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    const EXISTING_SOURCE = 'existing_source';
    const INPUT           = 'input';

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $input, Payout\Entity $payout)
    {
        $payoutId = $payout->getId();

        $this->trace->info(
            TraceCode::PAYOUT_SOURCE_ENTITY_CREATE_REQUEST,
            array_merge($input, [Entity::PAYOUT_ID => $payoutId])
        );

        (new Validator)->validateInput(Validator::PAYOUT_SOURCE_CREATE, $input);

        /** @var Entity $payoutSource */
        $payoutSource = $this->repo->payout_source->getPayoutSourceByPayoutIdAndPriority(
            $payoutId,
            $input[Entity::PRIORITY]);


        if ($payoutSource !== null)
        {
            throw new BadRequestException(
                ErrorCode::PAYOUT_SOURCE_ALREADY_EXISTS,
                null,
                [
                    self::EXISTING_SOURCE => $payoutSource->toArrayInternal(),
                    self::INPUT           => $input,
                    Entity::PAYOUT_ID     => $payoutId,
                ]
            );
        }

        $payoutSource = (new Entity);

        $payoutSource->payout()->associate($payout);

        $payoutSource = $payoutSource->build($input);

        $payoutSource->saveOrFail();

        $this->trace->info(
            TraceCode::PAYOUT_SOURCE_ENTITY_CREATED,
            $payoutSource->toArrayInternal()
        );
    }
}
