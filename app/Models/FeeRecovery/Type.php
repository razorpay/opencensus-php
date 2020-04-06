<?php

namespace RZP\Models\FeeRecovery;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;

class Type
{
    // 'Credit' is for entities where we have to give back money to the merchant
    const CREDIT = 'credit';

    // 'Debit' is for entities where we have to recover money from the merchant
    const DEBIT = 'debit';

    // Function used by admin fetch
    public static function getAll()
    {
        return [
            self::CREDIT,
            self::DEBIT,
        ];
    }

    /**
     * Returns Debit for: Payouts
     * Returns Credit for: Failed Payouts, Reversals
     *
     * Type is 'debit' corresponding to entities where we have to recover fees
     * Type is 'credit' corresponding to entities where we have to give back fees
     *
     * @param Base\PublicEntity $entity
     *
     * @return string
     * @throws LogicException
     */
    public function getTypeFromSourceEntity(Base\PublicEntity $entity)
    {
        $entityName = $entity->getEntityName();

        if ($entityName === Entity::PAYOUT)
        {
            if ($entity->getStatus() === Payout\Status::FAILED)
            {
                return self::CREDIT;
            }

            return self::DEBIT;
        }

        if ($entityName === Entity::REVERSAL)
        {
            return self::CREDIT;
        }

        throw new LogicException('No fee_recovery type exists for entity ' . $entityName . ', id ' . $entity->getId(),
                                 ErrorCode::BAD_REQUEST_LOGIC_ERROR_NO_FEE_RECOVERY_TYPE_FOR_GIVEN_ENTITY,
                                 [
                                     'entity_name'  => $entityName,
                                     'entity_id'    => $entity->getId(),
                                 ]);
    }

    public static function validateType(string $type)
    {
        $allowedTypes = self::getAll();

        if (in_array($type, $allowedTypes, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_INVALID_TYPE,
                null,
                [
                    'type' => $type,
                ]);
        }
    }
}
