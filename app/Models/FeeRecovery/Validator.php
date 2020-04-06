<?php

namespace RZP\Models\FeeRecovery;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const CREATE_FEE_RECOVERY_PAYOUT = 'create_fee_recovery_payout';

    // Currently fee_recovery is only used by the payouts model
    const ALLOWED_SOURCE_ENTITIES = [
        Entity::PAYOUT,
        Entity::REVERSAL,
    ];

    protected static $createFeeRecoveryPayoutRules = [
        Entity::BALANCE_ID          => 'required|string|size:14',
        Entity::FROM                => 'required|filled|integer',
        Entity::TO                  => 'required|filled|integer',
    ];

    // Function used by admin fetch
    public static function getAllSourceEntities()
    {
        return self::ALLOWED_SOURCE_ENTITIES;
    }

    public function validateBalanceTypeAndTimeStamps(Balance\Entity $balance,
                                                     $startTimeStamp,
                                                     $endTimeStamp)
    {
        // This can happen if someone provides a primary or shared-banking Balance Id in the admin route.
        if (($balance->isTypeBanking() === false) or
            ($balance->isAccountTypeDirect() === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_INCORRECT_BALANCE,
                null,
                [
                    'type'          => $balance->getType(),
                    'account_type'  => $balance->getAccountType(),
                    'balance_id'    => $balance->getId(),
                ]);
        }

        if ($endTimeStamp < $startTimeStamp)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_INCORRECT_TIMESTAMPS,
                null,
                [
                    'start_time_stamp'  => $startTimeStamp,
                    'end_time_stamp'    => $endTimeStamp,
                ]);
        }
    }

    /**
     * @param PublicEntity $entity
     *
     * @throws BadRequestException
     */
    public static function validateSourceEntity(PublicEntity $entity)
    {
        $entityName = $entity->getEntityName();

        if (in_array($entityName, self::getAllSourceEntities(), true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_CREATE_ATTEMPT_INVALID_SOURCE_ENTITY,
                null,
                [
                    'entity_name'   => $entityName,
                    'entity_id'     => $entity->getId(),
                ]);
        }
    }
}
