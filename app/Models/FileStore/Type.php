<?php

namespace RZP\Models\FileStore;

use RZP\Constants;
use RZP\Exception;

class Type
{
    const KOTAK_NETBANKING_REFUND   = 'kotak_netbanking_refund';

    const BATCH_INPUT               = 'batch_input';

    const BATCH_OUTPUT              = 'batch_output';

    const BLANK                     = 'blank';

    /**
     * Map of types allowed for each entity.
     */
    const TYPE_MAP = [
        self::BLANK => [
            self::KOTAK_NETBANKING_REFUND,
        ],
        Constants\Entity::BATCH => [
            self::BATCH_INPUT,
            self::BATCH_OUTPUT,
        ],
    ];

    /**
     * Types allowed when no entity is associated
     */
    const SHARED_ACCOUNT_ALLOWED_TYPES = [
        self::KOTAK_NETBANKING_REFUND,
    ];

    public static function validateType($type)
    {
        foreach(self::TYPE_MAP as $entity => $typeArray)
        {
            if (in_array($type, $typeArray) === true)
            {
                return true;
            }
        }

        throw new Exception\LogicException('Not A Valid Type: '. $type);
    }

    /**
     * Check if Filestore Type is valid for shared account
     *
     * @param $type    Filestore type value
     * @return boolean
     */
    public static function isTypeForSharedAccount($type)
    {
        return (in_array($type, self::SHARED_ACCOUNT_ALLOWED_TYPES) == true);
    }
}
