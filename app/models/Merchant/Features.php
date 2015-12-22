<?php

namespace Models\Merchant;

class Features
{
    public static $allowedBetaFeatures = array('dummy', 'webhooks');

    public static function validateFeatures($input)
    {
        if (isset($input[Entity::BETA_FEATURES]) === false or trim($input[Entity::BETA_FEATURES]) === '')
            return;

        $features = $input[Entity::BETA_FEATURES];
        $features = explode(',', $features);

        foreach ($features as $feature)
        {
            $feature = trim($feature); // Remove whitespace
            if (in_array($feature, Features::$allowedBetaFeatures) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The provided beta feature is invalid: $feature",
                    Entity::BETA_FEATURES
                );
            }
        }
    }
}
