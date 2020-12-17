<?php

namespace RZP\Models\AppStore;

class Repository extends \RZP\Base\Repository
{

    protected $entity = 'app_store';

    /**
     * Get AppStore Entity with AppName and MerchantId
     *
     * @param string $appName
     * @param string $merchantId
     *
     * @return Entity
     */
    public function getAppStoreDetailsForMerchant(string $appName, string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::APP_NAME, $appName)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->first();
    }

    /**
     * Gets all the app_names present for the merchantId
     *
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getAllAppsForMerchantFromAppStore(string $merchantId)
    {
        return $this->newQuery()
                    ->select(Entity::APP_NAME)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }

    /**
     * @param string $appName
     * @param string $mobileNumber
     *
     * @return Entity
     */
    public function getAppLinkedWithMobileNumber(string $appName, string $mobileNumber)
    {
        return $this->newQuery()
                    ->where(Entity::MOBILE_NUMBER, $mobileNumber)
                    ->where(Entity::APP_NAME, $appName)
                    ->first();
    }
}
