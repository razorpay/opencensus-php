<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class ProcessingTimeParser extends BaseParser
{
    protected static $properties = [
        Offer\Entity::PROCESSING_TIME
    ];

    protected static $localSuffix  = '.';

    protected static $delimiter    = ' ';

    const NUMBER_OF_SECONDS_IN_DAY = 86400;

    public static function getProcessingTimeDescription(Offer\Entity $offer)
    {
        $description = '';

        $processingTime = $offer->getProcessingTime();

        if ($processingTime > 0)
        {
            $processingTimeInDays = static::convertSecondsToDays($processingTime);

            $description .= 'Cashback will get credited in ' . $processingTimeInDays . ' business day(s)';
        }

        return $description;
    }

    public static function convertSecondsToDays(float $processingTime)
    {
        return ceil($processingTime / static::NUMBER_OF_SECONDS_IN_DAY);
    }
}
