<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
         Entity::PAYMENT_ID                 => 'required|alpha_num|size:14',
         Entity::CHECKOUT_ID                => 'sometimes|alpha_num|size:14',
         Entity::ATTEMPTS                   => 'sometimes|integer|min:0',
         Entity::LIBRARY                    => 'sometimes',
         Entity::PLATFORM                   => 'sometimes',
         Entity::BROWSER                    => 'sometimes',
         Entity::OS                         => 'sometimes',
         Entity::DEVICE                     => 'sometimes',
         Entity::REFERER                    => 'sometimes|url',
         Entity::USER_AGENT                 => 'required|string',
         Entity::IP                         => 'required|ip',

     );

    protected static $metadataValidators = array(
        'create');


    protected function validateCreate($metadata)
    {
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

        if (isset($metadata[Entity::BROWSER]))
        {
            Metadata::validateBrowser($metadata[Entity::BROWSER]);
        }

        if (isset($metadata[Entity::OS]))
        {
            Metadata::validateOs($metadata[Entity::OS]);
        }

        if (isset($metadata[Entity::DEVICE]))
        {
            Metadata::validateDevice($metadata[Entity::DEVICE]);
        }
    }
}