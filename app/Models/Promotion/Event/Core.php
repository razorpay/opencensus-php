<?php

namespace RZP\Models\Promotion\Event;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $this->trace->info(TraceCode::PROMOTION_EVENT_CREATE_REQUEST,
            [
                'input'  => $input
            ]);

        $eventEntity = (new Entity)->build($input);

        $this->checkForExistingPromotionEvent($input);

        $this->repo->saveOrFail($eventEntity);

        $this->trace->info(TraceCode::PROMOTION_EVENT_CREATE_RESPONSE,
            ['event_id' => $eventEntity->getId(),]);

        return $eventEntity;
    }

    protected function checkForExistingPromotionEvent(array $input)
    {
        $existingEvents = $this->repo->promotion_event->getExistingEventWithSimilarDetails($input);

        if ($existingEvents->count() > 0)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ANOTHER_PROMOTION_EVENT_ALREADY_EXISTS,
                                          null,
                                          ['input' => $input]);
        }
    }
}
