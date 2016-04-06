<?php

namespace Models\Merchant;

use EE\Exception;

class Features
{
    public static $allowedFeatures = array(
        'dummy',
        'webhooks',
        'aggregator');

    public static function validateFeatures($input)
    {
        if (empty($input[Entity::FEATURES]))
        {
            return;
        }

        $features = $input[Entity::FEATURES];

        $features = explode(',', $features);

        foreach ($features as $feature)
        {
            $feature = trim($feature); // Remove whitespace

            if (in_array($feature, self::$allowedFeatures) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The provided beta feature is invalid: $feature",
                    Entity::FEATURES);
            }
        }
    }
}
