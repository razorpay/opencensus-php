<?php

namespace Models\Merchant;

class Features
{
    public static $allowedFeatures = array('dummy', 'webhooks');

    public static function validateFeatures($input)
    {
        if (empty($input[Entity::FEATURES]))
            return;

        $features = $input[Entity::FEATURES];
        $features = explode(',', $features);

        foreach ($features as $feature)
        {
            $feature = trim($feature); // Remove whitespace
            if (in_array($feature, Features::$allowedFeatures) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The provided beta feature is invalid: $feature",
                    Entity::FEATURES
                );
            }
        }
    }
}
