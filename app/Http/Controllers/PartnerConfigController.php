<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class PartnerConfigController extends Controller
{
    use Traits\HasCrudMethods;

    /**
     * Get config based on partner or application id instead of primary key
     *
     * @return mixed
     */
    public function getConfig()
    {
        $input = Request::all();

        $data  = $this->service()->fetch($input);

        return ApiResponse::json($data);
    }

    /**
     * Get all config for a given merchant
     *
     * @return mixed
     */
    public function fetchConfigByPartner()
    {
        $data  = $this->service()->fetchConfigByPartner();

        return ApiResponse::json($data);
    }

    public function bulkUpsert() {

        $input = Request::all();

        return $this->service()->bulkUpsertSubmerchantPartnerConfig($input);
    }

    public function createPartnersSubMerchantConfig()
    {
        $input = Request::all();

        $response =  $this->service()->createPartnersSubMerchantConfig($input);

        return ApiResponse::json($response);
    }

    public function updatePartnersSubMerchantConfig()
    {
        $input = Request::all();

        $response = $this->service()->updatePartnersSubMerchantConfig($input);

        return  ApiResponse::json($response);
    }
}
