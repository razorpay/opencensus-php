<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;
use Carbon\Carbon;

class OfferTimeValidityParser extends BaseParser
{
    protected static $properties = [
        Offer\Entity::ENDS_AT
    ];

    protected static $localPrefix = 'Valid till';

    protected static $localSuffix = '.';

    public static function getEndsAtDescription(Offer\Entity $offer)
    {
        $endsAt = $offer->getAttribute(Offer\Entity::ENDS_AT);

        $format = 'd-m-Y';

        $endsAt = Carbon::createFromTimestamp($endsAt, 'Asia/Kolkata')
                    ->format($format);

        $description = $endsAt;

        return $description;
    }
}
