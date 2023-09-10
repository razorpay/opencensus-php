<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

interface GetFieldsForEntityFromProtoInterface
{
    public function getCreatedAt(): int;
    public function getUpdatedAt() : int;

    public function getAuditId() : ?string;
}
