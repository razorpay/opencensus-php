<?php

namespace Models\Customer\App;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer\App;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'CustomerApps';

    public function fetchAppsByDeviceToken($deviceToken, $customer, $merchant)
    {
        $repo = $this->repo;

        return $repo::where(App\Entity::DEVICE_TOKEN, '=', $deviceToken)
                    ->where(App\Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(App\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->get();
    }
}
