<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;


class Factory
{
    public static function getEntityToProtoConvertor($entity, $saveResponse): ?GetFieldsForEntityFromProtoInterface{

        return match (get_class($entity)) {
            \RZP\Models\Merchant\Website\Entity::class => new Website($saveResponse),
            \RZP\Models\Merchant\Email\Entity::class => new Email($saveResponse),
            \RZP\Models\Merchant\BusinessDetail\Entity::class => new BusinessDetail($saveResponse),
            \RZP\Models\Merchant\Document\Entity::class => new Document($saveResponse),
            \RZP\Models\Merchant\Detail\Entity::class => new MerchantDetail($saveResponse),
            \RZP\Models\Merchant\Stakeholder\Entity::class => new Stakeholder($saveResponse),
            \RZP\Models\Merchant\Entity::class => new Merchant($saveResponse),
            \RZP\Models\Address\Entity::class => new Address($saveResponse),
            default => null,
        };
    }
}
