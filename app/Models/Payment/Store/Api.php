<?php

namespace RZP\Models\Payment\Store;

use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Request\Requests;

class Api extends Base
{
    /**
     * To fetch a storeIds for a given user from Store Service microservice
     * @return array
     */
    public function fetchStores() : array
    {
        $config = config('applications.store_service');
        $baseUrl = $config['url'];
        $passport = $this->ba->getPassport();

        if ($passport[self::CONSUMER][self::TYPE] === BasicAuth::PASSPORT_CONSUMER_TYPE_USER) {
            $stores = $this->sendRequest(Requests::GET, $baseUrl);
            return empty($stores) ? [] : $this->formatStores($stores);
        }

        return [];
    }

    private function formatStores(mixed $stores) : array
    {
        $storeIds = [];

        foreach ($stores as $store) {
            $storeIds[] = $store['id'];
        }

        return $storeIds;
    }
}

