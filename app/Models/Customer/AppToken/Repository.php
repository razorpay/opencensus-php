<?php

namespace RZP\Models\Customer\AppToken;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer\AppToken;

class Repository extends Base\Repository
{
    protected $entity = 'app_token';

    protected $entityFetchParamRules = array(
        AppToken\Entity::ID            => 'sometimes|string|size:14',
        AppToken\Entity::CUSTOMER_ID   => 'sometimes|string|size:14',
        AppToken\Entity::DEVICE_TOKEN  => 'sometimes|string|size:14',
    );

    protected $appFetchParamRules = array(
        AppToken\Entity::CUSTOMER_ID   => 'sometimes|alpha_num',
        AppToken\Entity::MERCHANT_ID   => 'sometimes|alpha_num',
        AppToken\Entity::DEVICE_TOKEN  => 'sometimes|alpha_num',
    );

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function fetchAppsByDeviceToken($customer, $deviceToken)
    {
        return $this->newQuery()
                    ->where(AppToken\Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(AppToken\Entity::DEVICE_TOKEN, '=', $deviceToken)
                    ->get();
    }

    public function fetchByDeviceTokenAndMerchant($deviceToken, $merchant)
    {
        return $this->newQuery()
                    ->where(AppToken\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(AppToken\Entity::DEVICE_TOKEN, '=', $deviceToken)
                    ->get();
    }
}
