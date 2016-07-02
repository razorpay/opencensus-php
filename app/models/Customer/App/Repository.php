<?php

namespace Models\Customer\App;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer\App;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'AppToken';

    protected $entityFetchParamRules = array(
        App\Entity::ID            => 'sometimes|string|size:14',
        App\Entity::CUSTOMER_ID   => 'sometimes|string|size:14',
        App\Entity::DEVICE_TOKEN  => 'sometimes|string|size:14',
    );

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function fetchAppsByDeviceToken($customer, $deviceToken)
    {
        $repo = $this->repo;

        return $repo::where(App\Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(App\Entity::DEVICE_TOKEN, '=', $deviceToken)
                    ->get();
    }

    public function fetchByDeviceTokenAndMerchant($deviceToken, $merchant)
    {
        $repo = $this->repo;

        return $repo::where(App\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(App\Entity::DEVICE_TOKEN, '=', $deviceToken)
                    ->get();
    }
}
