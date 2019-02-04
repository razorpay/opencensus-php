<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;

class Constants
{
    const PAYMENT = 'payment';

    /**
     * List of entities for which the commission can be rolled out.
     * The entities defined here must implement the CommissionSourceInterface.
     *
     * @var array
     */
    public static $sourceEntities = [
        self::PAYMENT,
    ];

    /**
     * @return array
     */
    public static function getSourceEntities(): array
    {
        return self::$sourceEntities;
    }

    /**
     * @param Base\PublicEntity $entity
     *
     * @return bool
     */
    public static function isValidCommissionSource(Base\PublicEntity $entity): bool
    {
        $entityType = $entity->getEntity();

        return (in_array($entityType, self::getSourceEntities(), true) === true);
    }
}
