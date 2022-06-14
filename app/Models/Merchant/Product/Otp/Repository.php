<?php

namespace RZP\Models\Merchant\Product\Otp;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_otp_verification_logs';


    public function findMerchantOtpLogByMidAndContactNumber(string $merchantId, string $contactNumber): ?Entity
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::CONTACT_MOBILE, '=', $contactNumber)
                    ->first();
    }

    public function isOtpVerificationLogExist(string $merchantId, string $contactNumber)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::CONTACT_MOBILE, '=', $contactNumber)
                    ->exists();
    }

}
