<?php

namespace RZP\Models\Customer\AppToken;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Customer\AppToken;

class Core extends Base\Core
{
    public function create($input, Customer\Entity $customer, Merchant\Entity $merchant)
    {
        $app = (new AppToken\Entity)->build($input);

        $app->customer()->associate($customer);

        $app->merchant()->associate($merchant);

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

    public function getAppByAppTokenId($appTokenId, Merchant\Entity $merchant): ?Entity
    {
        $sharedMerchant = $this->repo->merchant->getSharedAccount();

        $app = $this->getAppByAppTokenIdAndMerchant($appTokenId, $sharedMerchant);

        if (($app === null) && ($merchant->getId() !== $sharedMerchant->getId()))
        {
            $app = $this->getAppByAppTokenIdAndMerchant($appTokenId, $merchant);
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

        assertTrue(($apps->count() > 1) === false);

        return $apps[0];
    }

    protected function getAppByAppTokenIdAndMerchant(string $appTokenId, Merchant\Entity $merchant): ?Entity
    {
        AppToken\Entity::verifyIdAndStripSign($appTokenId);

        $appToken = null;

        try
        {
            $appToken = $this->repo->app_token->findByIdAndMerchant(
                                            $appTokenId, $merchant);

            // Fetches customer and associates with app token
            $customer = $this->repo->customer->fetchByAppToken($appToken);
        }
        catch (Exception\BadRequestException $ex)
        {
            // ignore the exception, not tracing it as well as we are always trying
            // 2 merchant accounts and one will always fail so it will be noisy
        }

        return $appToken;
    }
}
