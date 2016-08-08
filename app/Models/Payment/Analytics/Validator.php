<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment\Metadata;

class Validator extends Base\Validator
{
    protected static $metadataRules = array(
        Entity::CHECKOUT_ID             => 'sometimes|alpha_num|size:14',
        Entity::PLATFORM                => 'sometimes',
        Entity::LIBRARY                 => 'sometimes',
    );

    protected static $metadataValidators = array(
        'metadata');


    protected function validateMetadata($metadata)
    {
        sd('validating?');
        if (isset($metadata[Entity::CHECKOUT_ID]))
        {
            Entity::validateCheckDigit($metadata[Entity::CHECKOUT_ID]);
        }

        if (isset($metadata[Entity::PLATFORM]))
        {
            Metadata::validatePlatform($metadata[Entity::PLATFORM]);
        }

        if (isset($metadata[Entity::LIBRARY]))
        {
            Metadata::validateLibrary($metadata[Entity::LIBRARY]);
        }
    }
}