<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class ProcessingTimeParser extends BaseParser
{
    protected $properties = [
        Offer\Entity::PROCESSING_TIME
    ];

    protected $localSuffix  = '.';

    protected $delimiter    = ' ';

    const NUMBER_OF_SECONDS_IN_DAY = 86400;

    protected function getProcessingTimeDescription()
    {
        $description = '';

        $processingTime = $this->offer->getProcessingTime();

        if ($processingTime > 0)
        {
            $processingTimeInDays = $this->convertSecondsToDays($processingTime);

            $description .= 'Cashback will get credited in ' . $processingTimeInDays . ' business day(s)';
        }

        return $description;
    }

    protected function convertSecondsToDays(float $processingTime)
    {
        return intval($processingTime / self::NUMBER_OF_SECONDS_IN_DAY);
    }
}
