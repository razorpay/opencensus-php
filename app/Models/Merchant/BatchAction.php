<?php

namespace RZP\Models\Merchant;

class BatchAction
{
    const UPDATE_ENTITY = 'update_entity';

    const BATCH_ACTIONS = [
        self::UPDATE_ENTITY,
    ];

    /**
     * this function checks whether given batch action is valid or not
     *
     * @param $BatchAction
     *
     * @return bool
     */
    public static function exists(string $BatchAction): bool
    {
        //if $BatchAction is batch_actions return false
        if (strtolower($BatchAction) === 'batch_actions')
        {
            return false;
        }

        return defined(get_class() . '::' . strtoupper($BatchAction));
    }

}
