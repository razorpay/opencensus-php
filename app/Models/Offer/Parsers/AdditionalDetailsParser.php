<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class AdditionalDetailsParser extends BaseParser
{
    protected static $properties = [
        Offer\Entity::ADDITIONAL_DETAILS
    ];

    public static function getAdditionalDetailsDescription(Offer\Entity $offer)
    {
        return $offer->getAdditionalDetails();
    }
}
