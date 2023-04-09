<?php

namespace RZP\Models\PayoutSource;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\DualWrite\PayoutSource as PayoutSourceDualWrite;

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

        $this->validateIfSameSourceIsNotAlreadyPresentForThePayout($input, $payoutId);

        $this->validateIfSourceIsNotPresentForThePayoutWithSamePriority($input, $payoutId);

        $payoutSource = (new Entity);

        $payoutSource->payout()->associate($payout);

        $payoutSource = $payoutSource->build($input);

        $payoutSource->saveOrFail();

        $this->trace->info(
            TraceCode::PAYOUT_SOURCE_ENTITY_CREATED,
            $payoutSource->toArrayInternal()
        );
    }

    protected function validateIfSameSourceIsNotAlreadyPresentForThePayout(array $input, string $payoutId)
    {
        /** @var Entity $payoutSource */
        $payoutSource = $this->repo->payout_source->getPayoutSourceBySourceIdSourceTypePayoutId(
            $input[Entity::SOURCE_ID],
            $input[Entity::SOURCE_TYPE],
            $payoutId);

        if ($payoutSource !== null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_SOURCE_ALREADY_EXISTS,
                null,
                [
                    self::EXISTING_SOURCE => $payoutSource->toArrayInternal(),
                    self::INPUT           => $input,
                    Entity::PAYOUT_ID     => $payoutId,
                ]
            );
        }
    }

    protected function validateIfSourceIsNotPresentForThePayoutWithSamePriority(array $input, string $payoutId)
    {
        /** @var Entity $payoutSource */
        $payoutSource = $this->repo->payout_source->getPayoutSourceByPayoutIdAndPriority(
            $payoutId,
            $input[Entity::PRIORITY]);

        if ($payoutSource !== null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ANOTHER_PAYOUT_SOURCE_EXISTS_WITH_SAME_PRIORITY,
                null,
                [
                    self::EXISTING_SOURCE => $payoutSource->toArrayInternal(),
                    self::INPUT           => $input,
                    Entity::PAYOUT_ID     => $payoutId,
                ]
            );
        }
    }

    public function getPayoutSource(string $payoutId)
    {
        /** @var Entity $payoutSource */
        $payoutSource = $this->repo->payout_source->getPayoutSourceByPayoutIdAndPriority(
            $payoutId, 1);

        if (empty($payoutSource) === true)
        {
            $payoutSources = (new PayoutSourceDualWrite)->getAPIPayoutSourcesFromPayoutService($payoutId);

            if (empty($payoutSources) === false)
            {
                $payoutSource = array_values($payoutSources)[0];
            }
        }

        return $payoutSource;
    }

}
