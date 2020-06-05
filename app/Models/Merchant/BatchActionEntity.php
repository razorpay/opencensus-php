<?php

namespace RZP\Models\Merchant;

class BatchActionEntity
{
    const MERCHANT_DETAIL = 'merchant_detail';
    const MERCHANT = 'merchant';

    const BATCH_ACTION_ENTITIES = [
        self::MERCHANT_DETAIL,
        self::MERCHANT
    ];

    /**
     * this function checks whether given batch action Entity is valid or not
     *
     * @param $batchActionEntity
     *
     * @return bool
     */
    public static function exists(string $batchActionEntity): bool
    {
        //if $batchActionEntity is batch_action_entities return false
        if (strtolower($batchActionEntity) === 'batch_action_entities')
        {
            return false;
        }

        return defined(get_class() . '::' . strtoupper($batchActionEntity));
    }
}
