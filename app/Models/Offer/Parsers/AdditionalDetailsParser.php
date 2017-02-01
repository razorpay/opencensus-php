<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class AdditionalDetailsParser extends BaseParser
{
    protected $properties = [
        Offer\Entity::ADDITIONAL_DETAILS
    ];

    protected function getAdditionalDetailsDescription()
    {
        return $this->offer->getAdditionalDetails();
    }
}
