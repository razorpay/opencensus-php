<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;


class Factory
{
    public static function getEntityToProtoConvertor($entity, $saveResponse): ?GetFieldsForEntityFromProtoInterface{

        return match (get_class($entity)) {
            \RZP\Models\Merchant\Website\Entity::class => new Website($saveResponse),
            \RZP\Models\Merchant\Email\Entity::class => new Email($saveResponse),
            \RZP\Models\Merchant\BusinessDetail\Entity::class => new BusinessDetail($saveResponse),
            default => null,
        };
    }
}
