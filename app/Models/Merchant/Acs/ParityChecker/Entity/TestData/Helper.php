<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity\TestData;



use RZP\Models\Base\UniqueIdEntity;

class Helper {

    public static function getUniqueIdCallBack() {
        return function () {
            return UniqueIdEntity::generateUniqueId();
        };
    }

}
