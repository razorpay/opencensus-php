<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;

class Factory
{
    public static function getEntityToProtoConvertor($entity, $saveResponse): ?GetFieldsForEntityFromProtoInterface{
        if ($entity instanceof \RZP\Models\Merchant\Entity) {
            return new Merchant($saveResponse);
        }

        return null;
    }
}
