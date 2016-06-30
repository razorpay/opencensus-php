<?php

namespace Models\Customer\App;

use Models\Base;
use Models\Customer\App;

class Core extends Base\Core
{
    public function create($input)
    {
        $app = (new App\Entity)->build($input);

        $this->repo->saveOrFail($app);

        return $app;
    }

    public function deleteAppTokensForGlobalCustomer($customer, $input)
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

        $apps = $this->repo->app_token->fetch($params);

        if ($apps !== null)
        {
            foreach ($apps as $app)
            {
                $this->repo->deleteOrFail($app);
            }
        }

        return [];
    }

    public function getAppTokenByDeviceTokenAndMerchant($deviceToken, $merchant)
    {
        $apps = $this->repo->app_token->fetchByDeviceTokenAndMerchant(
            $deviceToken, $merchant);

        if ($apps->count() === 0)
        {
            return null;
        }

        assert(($apps->count() > 1) === false);

        return $apps[0];
    }
}