<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

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

    public function bulkUpdateOnboardingSource()
    {
        $input = Request::all();

        $response = $this->service()->bulkUpdateOnboardingSource($input);

        return  ApiResponse::json($response);
    }

    /**
     * @param string $id
     * @return mixed
     *
     * @throws BadRequestException
     */
    public function uploadLogo(string $id)
    {
        if (Request::hasFile('logo'))
        {
            $input['logo'] = Request::file('logo');

            $response = $this->service()->uploadLogo($id, $input);

            return ApiResponse::json($response);
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_LOGO_NOT_PRESENT);
        }
    }
}
