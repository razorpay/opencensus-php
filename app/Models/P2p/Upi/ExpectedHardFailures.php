<?php

namespace RZP\Models\P2p\Upi;

use Exception;
use RZP\Models\P2p\Mandate;
use RZP\Models\P2p\Transaction;

/**
 * Class ExpectedHardFailures
 *
 * @package RZP\Models\P2p\Upi
 *
 * This class contains static rules for which hard failures needs to be thrown
 * To add a static rule for hard failure, identify the action and try filtering the exception message in the $map
 */
class ExpectedHardFailures
{
    const KEY = 'expected_hard_failure';

    protected static $map = [
        Transaction\Entity::TRANSACTION => [
            Transaction\Action::INCOMING_COLLECT              => [
                'First transaction can not be more than 5000 rupees.',
            ],
            Transaction\Action::AUTHORIZE_TRANSACTION_SUCCESS => [
                'Count of UPI should be exactly one',
            ],
        ],

        Mandate\Entity::MANDATE => [

        ],
    ];

    // This will check for expected hard failure for all gateway
    // TODO: If needed we can make this gateway specific by taking gateway as parameter
    public static function isExpected(Exception $e, array $context): bool
    {
        if (isset($context[Transaction\Entity::ENTITY]) === true and
            isset($context[Transaction\Entity::ACTION]) === true)
        {
            $entityFailures = self::$map[$context[Transaction\Entity::ENTITY] ?? ''];

            if (isset($entityFailures[$context[Transaction\Entity::ACTION]]) === true)
            {
                $msg = $e->getMessage();

                $actionFailures = $entityFailures[$context[Transaction\Entity::ACTION] ?? ''];

                return in_array($msg, $actionFailures, true) === true;
            }
        }

        return false;
    }
}
