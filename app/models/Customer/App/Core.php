<?php

namespace Models\Customer\App;

use Models\Base;
use Models\Customer\App;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new App\Repository;
    }

    public function create($input)
    {
        $app = (new App\Entity)->build($input);

        $this->repo->saveOrFail($app);

        return $app;
    }

    public function validateDeviceToken($deviceToken, $customer, $merchant)
    {
        $valid = false;

        $apps = $this->repo->fetchAppsByDeviceToken($deviceToken, $customer, $merchant);

        if ($apps !== null)
        {
            $valid = true;
        }

        return $valid;
    }

    public function deleteCustomerTokens($customer, $input)
    {
        $params = array(
            App\Entity::CUSTOMER_ID     => $customer->getId()
        );

        if ($input['logout'] === 'app')
        {
            $params[App\Entity::ID] = App\Entity::verifyIdAndStripSign($input['app_token']);
            $params[App\Entity::DEVICE_TOKEN] = $input[App\Entity::DEVICE_TOKEN];
        }
        else if ($input['logout'] === 'device')
        {
            $params[App\Entity::DEVICE_TOKEN] = $input[App\Entity::DEVICE_TOKEN];
        }

        $apps = $this->repo->fetch($params);

        if ($apps !== null)
        {
            foreach ($apps as $app)
            {
                $this->repo->deleteOrFail($app);
            }
        }

        return [];
    }
}