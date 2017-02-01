<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;
use Carbon\Carbon;

class OfferTimeValidityParser extends BaseParser
{
    protected $properties = [
        Offer\Entity::ENDS_AT
    ];

    protected $localPrefix = 'Valid till';

    protected $localSuffix = '.';

    protected function getEndsAtDescription()
    {
        $endsAt = $this->offer->getAttribute(Offer\Entity::ENDS_AT);

        $format = 'd-m-Y';

        $endsAt = Carbon::createFromTimestamp($endsAt, 'Asia/Kolkata')
                    ->format($format);

        $description = $endsAt;

        return $description;
    }
}
