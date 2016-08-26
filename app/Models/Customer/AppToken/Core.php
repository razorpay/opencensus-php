<?php

namespace RZP\Models\Customer\AppToken;

use RZP\Models\Base;
use RZP\Models\Customer\AppToken;
use RZP\Exception;

class Core extends Base\Core
{
    public function create($input)
    {
        $app = (new AppToken\Entity)->build($input);

        $this->repo->saveOrFail($app);

        return $app;
    }

    public function deleteAppTokens($app, $input)
    {
        $params = array(
            AppToken\Entity::CUSTOMER_ID     => $app->customer->getId()
        );

        if ($input['logout'] === 'app')
        {
            $params[AppToken\Entity::ID] = $app->getId();
            $params[AppToken\Entity::DEVICE_TOKEN] = $app->getDeviceToken();
        }
        else if ($input['logout'] === 'device')
        {
            $params[AppToken\Entity::DEVICE_TOKEN] = $app->getDeviceToken();
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

    public function getAppByAppToken($appToken, $merchant)
    {
        $app = null;

        try
        {
            $app = $this->repo->app_token->findByIdAndMerchantId(
                $appToken,
                $this->repo->merchant->getSharedAccount()->getId());
        }
        catch (Exception\BadRequestException $ex)
        {
            $app = $this->repo->app_token->findByIdAndMerchantId(
                $appToken,
                $merchant->getId());
        }

        return $app;
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