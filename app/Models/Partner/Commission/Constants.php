<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Constants
{
    const PAYMENT = 'payment';

    const COMMISSION_BREAK_UP_PREFIX = 'commission_';

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
     * Partner types which are eligible to get commissions even if the source transaction (payment, refund, etc) is
     * not originated by the partner via the partner auth or bearer auth (oauth).
     *
     * Commission will be calculated and rolled out only to the partner types defined here if the transactions do not
     * have the origin set to 'application'.
     *
     * @var array
     */
    public static $partnerTypesEligibleWithoutOrigin = [
        Merchant\Constants::RESELLER,
    ];

    /**
     * @return array
     */
    public static function getSourceEntities(): array
    {
        return self::$sourceEntities;
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    public static function isValidCommissionSource($entity): bool
    {
        $entityType = $entity->getEntity();

        return (in_array($entityType, self::getSourceEntities(), true) === true);
    }
}
