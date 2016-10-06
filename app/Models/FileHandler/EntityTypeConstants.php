<?php

namespace RZP\Models\FileHandler;

class EntityTypeConstants
{
    const KOTAK_NETBANKING_REFUND  = 'kotak_netbanking_refund';
    //TODO : Not populating it now
    //       As other Entity will be using it, it should be populated
    //
    public static function getValidEntity()
    {
        return [];
    }
}
