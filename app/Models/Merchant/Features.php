<?php

namespace RZP\Models\Merchant;

use RZP\Exception;

class Features
{
    const DUMMY         = 'dummy';
    const WEBHOOKS      = 'webhooks';
    const AGGREGATOR    = 'aggregator';
    const TOKENS        = 'tokens';
    const S2SWALLET     = 's2swallet';
    const SETL_REPORT   = 'setl_report';
    const CARD_SAVING   = 'cardsaving';
    const RECURRING     = 'recurring';
    const S2S           = 's2s';

    const DELIMITER     = ',';

    public static $allowedFeatures = array(
        self::DUMMY,
        self::WEBHOOKS,
        self::AGGREGATOR,
        self::TOKENS,
        self::S2SWALLET,
        self::SETL_REPORT,
        self::CARD_SAVING,
        self::RECURRING,
        self::S2S,
    );

    public static function validateFeatures($input)
    {
        if (empty($input[Entity::FEATURES]))
        {
            return;
        }

        $features = $input[Entity::FEATURES];

        $features = explode(self::DELIMITER, $features);

        foreach ($features as $feature)
        {
            $feature = trim($feature); // Remove whitespace

            if (in_array($feature, self::$allowedFeatures, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The provided beta feature is invalid: $feature",
                    Entity::FEATURES);
            }
        }
    }
}
