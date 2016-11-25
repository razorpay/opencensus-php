<?php

namespace RZP\Models\FileStore;

use RZP\Constants;
use RZP\Exception;

class Type
{
    const KOTAK_NETBANKING_REFUND   = 'kotak_netbanking_refund';

    const HDFC_NETBANKING_REFUND    = 'hdfc_netbanking_refund';

    const AIRTELMONEY_WALLET_REFUND = 'airtelmoney_wallet_refund';

    const PAYUMONEY_WALLET_REFUND   = 'payumoney_wallet_refund';

    const ICICI_UPI_REFUND          = 'icici_upi_refund';

    const BATCH_INPUT               = 'batch_input';

    const BATCH_OUTPUT              = 'batch_output';

    const BLANK                     = 'blank';

    /**
     * Map of types allowed for each entity.
     */
    const TYPE_MAP = [
        self::BLANK => [
            self::KOTAK_NETBANKING_REFUND,
            self::HDFC_NETBANKING_REFUND,
            self::AIRTELMONEY_WALLET_REFUND,
            self::PAYUMONEY_WALLET_REFUND,
            self::ICICI_UPI_REFUND,
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
        self::HDFC_NETBANKING_REFUND,
        self::AIRTELMONEY_WALLET_REFUND,
        self::PAYUMONEY_WALLET_REFUND,
        self::ICICI_UPI_REFUND,
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
