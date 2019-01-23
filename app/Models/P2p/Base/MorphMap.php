<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\P2p;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * For polymorphic relations eloquent stores complete
 * namespace to Model by default. Which makes database
 * strongly coupled with the code. In order to decouple
 * this, we are using Relation::morphMap feature, which
 * makes eloquent to use defined strings from models.
 *
 * Class MorphMap
 * @package RZP\Models\P2p\Base
 */
class MorphMap
{
    const BANK_ACCOUNT                  = 'bank_account';
    const VPA                           = 'vpa';

    const MAP = [
        self::BANK_ACCOUNT                  => P2p\BankAccount\Entity::class,
        self::VPA                           => P2p\Vpa\Entity::class,
    ];

    public static function boot()
    {
        Relation::morphMap(self::MAP);
    }

    public static function getClass(string $key)
    {
        return self::MAP[$key];
    }

    public static function getMorphed($entity): string
    {
        $class = get_class($entity);

        $map   = array_search($class, self::MAP, true);

        return $map;
    }
}
